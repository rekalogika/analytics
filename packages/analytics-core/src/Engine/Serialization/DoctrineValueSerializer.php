<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/analytics package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Analytics\Engine\Serialization;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Serialization\UnsupportedValue;
use Rekalogika\Analytics\Contracts\Serialization\ValueSerializer;
use Rekalogika\Analytics\Engine\Util\ProxyUtil;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;

final readonly class DoctrineValueSerializer implements ValueSerializer
{
    /**
     * Sentinel value to indicate null
     */
    public const NULL = "\x1E";

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}


    #[\Override]
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): string {
        // if value is enum, we return the value directly. no type checking is
        // done here.

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value === null) {
            return self::NULL;
        }

        if (\is_string($value) || \is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        if (!\is_object($value)) {
            throw new UnsupportedValue();
        }

        $class = ProxyUtil::normalizeClassName($value::class);
        $metadata = new ClassMetadataWrapper($this->managerRegistry, $class);

        // again, no checking is done here yet.
        return $metadata->getStringIdentifierFromObject($value);
    }

    #[\Override]
    public function deserialize(
        string $class,
        string $dimension,
        string $identifier,
    ): mixed {
        if ($identifier === self::NULL) {
            return null;
        }

        $metadata = new ClassMetadataWrapper($this->managerRegistry, $class);

        if (!$metadata->hasProperty($dimension)) {
            throw new MetadataException(\sprintf(
                'The class "%s" does not have a field named "%s"',
                $class,
                $dimension,
            ));
        }

        // manager

        $manager = $this->managerRegistry->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new MetadataException(\sprintf(
                'The class "%s" is not managed by Doctrine ORM',
                $class,
            ));
        }

        // get dimension metadata

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($class);

        // if it is a relation, we get the unique values from the source entity

        if ($metadata->isPropertyEntity($dimension)) {
            $relatedClass = $metadata->getAssociationTargetClass($dimension);

            return $manager->find($relatedClass, $identifier);
        }

        // if enum

        if (($enumType = $metadata->getEnumType($dimension)) !== null) {
            if (is_a($enumType, \BackedEnum::class, true)) {
                try {
                    return $enumType::from($identifier);
                } catch (\TypeError) {
                    return $enumType::from((int) $identifier);
                }
            }

            throw new UnexpectedValueException(\sprintf(
                'The enum type "%s" is not a BackedEnum',
                $enumType,
            ));
        }

        // if scalar

        if ($metadata->getScalarType($dimension) !== null) {
            return $identifier;
        }

        throw new UnsupportedValue();
    }
}
