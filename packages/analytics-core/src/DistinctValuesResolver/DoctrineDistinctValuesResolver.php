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

namespace Rekalogika\Analytics\DistinctValuesResolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\DistinctValuesResolver;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\DimensionMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\SummaryManager\Query\GetDistinctValuesFromSourceQuery;

final class DoctrineDistinctValuesResolver implements DistinctValuesResolver
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly SummaryMetadataFactory $summaryMetadataFactory,
    ) {}

    public static function getApplicableDimensions(): ?iterable
    {
        return null;
    }

    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): null|iterable {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        // make sure the field exists

        $metadata = new ClassMetadataWrapper($manager->getClassMetadata($class));

        if (!$metadata->hasProperty($dimension)) {
            throw new \InvalidArgumentException(\sprintf(
                'The class "%s" does not have a field named "%s"',
                $class,
                $dimension,
            ));
        }

        // get dimension metadata

        $summaryMetadata = $this->summaryMetadataFactory
            ->getSummaryMetadata($class);

        $dimensionMetadata = $summaryMetadata->getDimensionMetadata($dimension);

        // if it is a relation, we get the unique values from the source entity

        if ($metadata->isPropertyEntity($dimension)) {
            $relatedClass = $metadata->getAssociationTargetClass($dimension);

            return $this->getRelationDistinctValues(
                $relatedClass,
                $dimensionMetadata,
                $limit,
            );
        }

        // if enum

        if (($enumType = $metadata->getEnumType($dimension)) !== null) {
            return $enumType::cases();
        }

        // otherwise we get the unique values from the summary table

        return $this->getDistinctValuesFromSummary(
            class: $class,
            dimensionMetadata: $dimensionMetadata,
            limit: $limit,
        );
    }

    /**
     * @param class-string $class
     * @return iterable<object>
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

        $queryBuilder = $manager->createQueryBuilder();

        $query = new GetDistinctValuesFromSourceQuery(
            dimensionMetadata: $dimensionMetadata,
            queryBuilder: $queryBuilder,
            limit: $limit,
        );

        /** @var array<object> */
        return $query->getResult();
    }

    /**
     * @param class-string $class
     * @return iterable<object>
     */
    private function getDistinctValuesFromSummary(
        string $class,
        DimensionMetadata $dimensionMetadata,
        int $limit,
    ): iterable {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException('Invalid manager');
        }

        $queryBuilder = $manager->createQueryBuilder();
        $dimension = $dimensionMetadata->getSummaryProperty();

        $orderBy = $dimensionMetadata->getOrderBy();

        $queryBuilder = $manager->createQueryBuilder()
            ->select("DISTINCT e.$dimension")
            ->from($class, 'e')
            ->orderBy("e.$dimension")
            ->setMaxResults($limit);

        /** @var list<object> */
        return $queryBuilder->getQuery()->getArrayResult();
    }
}
