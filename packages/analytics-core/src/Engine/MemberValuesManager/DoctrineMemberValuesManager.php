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

namespace Rekalogika\Analytics\Engine\MemberValuesManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\MemberValuesManager;
use Rekalogika\Analytics\Engine\SummaryManager\Query\GetDistinctValuesFromSourceQuery;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class DoctrineMemberValuesManager implements MemberValuesManager
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private SummaryMetadataFactory $summaryMetadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
    ) {}

    #[\Override]
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): null|iterable {
        // make sure the field exists

        $metadata = new ClassMetadataWrapper($this->managerRegistry, $class);

        if (!$metadata->hasProperty($dimension)) {
            throw new MetadataException(\sprintf(
                'The class "%s" does not have a field named "%s"',
                $class,
                $dimension,
            ));
        }

        // get dimension metadata

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($class);

        $dimensionMetadata = $summaryMetadata->getDimension($dimension);

        // if it is a relation, we get the unique values from the source entity

        if ($metadata->isPropertyEntity($dimension)) {
            $relatedClass = $metadata->getAssociationTargetClass($dimension);

            return $this->getRelationDistinctValues(
                class: $relatedClass,
                dimensionMetadata: $dimensionMetadata,
                limit: $limit,
            );
        }

        // if enum

        $enumType = $metadata->getEnumType($dimension)
            ?? $dimensionMetadata->getTypeClass();

        if ($enumType !== null && is_a($enumType, \BackedEnum::class, true)) {
            return (function () use ($enumType) {
                foreach ($enumType::cases() as $case) {
                    yield (string) $case->value => $case;
                }
            })();
        }

        return null;
    }

    /**
     * @param class-string $class
     * @return iterable<string,object>
     */
    private function getRelationDistinctValues(
        string $class,
        DimensionMetadata $dimensionMetadata,
        int $limit,
    ): null|iterable {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        $query = new GetDistinctValuesFromSourceQuery(
            dimensionMetadata: $dimensionMetadata,
            entityManager: $manager,
            limit: $limit,
            propertyAccessor: $this->propertyAccessor,
        );

        return $query->getResult();
    }
}
