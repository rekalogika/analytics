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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
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
     * Doctrine types whose PHP values are objects. They are left to other
     * serializers.
     */
    private const OBJECT_TYPES = [
        Types::DATE_MUTABLE,
        Types::DATE_IMMUTABLE,
        Types::DATEINTERVAL,
        Types::DATETIME_MUTABLE,
        Types::DATETIME_IMMUTABLE,
        Types::DATETIMETZ_MUTABLE,
        Types::DATETIMETZ_IMMUTABLE,
        Types::TIME_MUTABLE,
        Types::TIME_IMMUTABLE,
    ];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $summaryMetadataFactory,
    ) {}


    #[\Override]
    public function serialize(
        string $class,
        string $dimension,
        mixed $value,
    ): int|string|null {
        // if value is enum, we return the value directly. no type checking is
        // done here.

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value === null) {
            return null;
        }

        if (\is_string($value) || \is_int($value)) {
            return $value;
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (!\is_object($value)) {
            throw new UnsupportedValue();
        }

        // only entities with a single identifier convertible to a string are
        // handled here. other objects are left to other serializers.

        $class = ProxyUtil::normalizeClassName($value::class);
        $manager = $this->managerRegistry->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new UnsupportedValue();
        }

        $metadata = new ClassMetadataWrapper($manager, $class);

        if (!$metadata->isEntity()) {
            throw new UnsupportedValue();
        }

        try {
            return $metadata->getStringIdentifierFromObject($value);
        } catch (MetadataException) {
            throw new UnsupportedValue();
        }
    }

    #[\Override]
    public function deserialize(
        string $class,
        string $dimension,
        int|string|null $identifier,
    ): mixed {
        if ($identifier === null) {
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

        // make sure the class is a summary class

        $this->summaryMetadataFactory->getSummaryMetadata($class);

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

        // if boolean

        $scalarType = $metadata->getScalarType($dimension);

        if ($scalarType === Types::BOOLEAN) {
            return filter_var($identifier, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE)
                ?? throw new InvalidArgumentException(\sprintf(
                    'Invalid boolean identifier "%s" for dimension "%s" of class "%s"',
                    $identifier,
                    $dimension,
                    $class,
                ));
        }

        // if scalar

        if ($scalarType !== null && !\in_array($scalarType, self::OBJECT_TYPES, true)) {
            return $identifier;
        }

        throw new UnsupportedValue();
    }
}
