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

namespace Rekalogika\Analytics\SummaryManager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Exception\OverflowException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\SummaryManager\DefaultQuery;
use Rekalogika\Analytics\Util\PartitionUtil;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class SummarizerQuery extends AbstractQuery
{
    /**
     * @var list<string>
     */
    private array $rollUpFields = [];

    /**
     * @var list<string>
     */
    private array $groupingFields = [];

    /**
     * @var array<string,bool>
     */
    private array $groupings = [];

    /**
     * @var array<string,string>
     */
    private array $dimensionAliases = [];

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $queryResult = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly int $queryResultLimit,
    ) {
        $summaryClass = $metadata->getSummaryClass();

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $summaryClass,
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $dimensionsInQuery = $this->query->getGroupBy();

        if (!\in_array('@values', $dimensionsInQuery, true)) {
            $dimensionsInQuery[] = '@values';
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function getQueryResult(): array
    {
        if ($this->queryResult !== null) {
            return $this->queryResult;
        }

        // check if select is empty
        if ($this->query->getSelect() === []) {
            return $this->queryResult = [];
        }

        // add query builder parameters that are always used
        $this->initializeQueryBuilder();

        // add query parameters involving dimensions to the query builder
        $this->processAllDimensions();

        // add partition where clause
        if (!$this->addPartitionWhere()) {
            return $this->queryResult = [];
        }

        // add where clause supplied by the user
        $this->addUserSuppliedWhere();

        // add grouping where clause
        $this->addGroupingWhere();

        // add order by clause supplied by the user
        $this->addUserSuppliedOrderBy();

        // create grouping field

        $this->getSimpleQueryBuilder()->addSelect(\sprintf(
            "REKALOGIKA_GROUPING_CONCAT(%s) AS __grouping",
            implode(', ', $this->groupingFields),
        ));

        // create group by

        $rollUp = new RollUp();

        foreach ($this->rollUpFields as $field) {
            // $this->getSimpleQueryBuilder()->addGroupBy($field);
            $rollUp->add(new Field($field));
        }

        $groupBy = new GroupBy();
        $groupBy->add($rollUp);

        // create query & apply group by

        $query = $this->getSimpleQueryBuilder()->getQuery();

        if (\count($groupBy) > 0) {
            $groupBy->apply($query);
        }

        // get result
        /** @var list<array<string,mixed>> */
        $result = $query->getArrayResult();

        // check safeguard

        if (\count($result) > $this->queryResultLimit) {
            throw new OverflowException(\sprintf(
                'The query result exceeds the safeguard limit of %d. Modify your query to return less records.',
                $this->queryResultLimit,
            ));
        }

        // change alias to dimension name

        $newResult = [];

        foreach ($result as $row) {
            $newRow = [];

            /** @var mixed $value */
            foreach ($row as $key => $value) {
                if (\array_key_exists($key, $this->dimensionAliases)) {
                    /** @psalm-suppress MixedAssignment */
                    $newRow[$this->dimensionAliases[$key]] = $value;
                } else {
                    /** @psalm-suppress MixedAssignment */
                    $newRow[$key] = $value;
                }
            }

            $newResult[] = $newRow;
        }

        return $this->queryResult = $newResult;
    }

    private function initializeQueryBuilder(): void
    {
        $this->getSimpleQueryBuilder()
            ->setMaxResults($this->queryResultLimit + 1) // safeguard
        ;

        foreach ($this->metadata->getDimensionPropertyNames() as $propertyName) {
            $this->groupings[$propertyName] = true;
        }
    }

    /**
     * @return iterable<Comparison|Andx>
     */
    private function getRangeConditions(Partition $partition): iterable
    {
        $summaryProperty = $this->metadata
            ->getPartition()
            ->getSummaryProperty();

        $partitionLevelProperty = $this->metadata
            ->getPartition()
            ->getPartitionLevelProperty();

        $partitionKeyProperty = $this->metadata
            ->getPartition()
            ->getPartitionKeyProperty();

        $higherPartition = $partition->getContaining();

        $levelProperty = \sprintf(
            'root.%s.%s',
            $summaryProperty,
            $partitionLevelProperty,
        );

        $keyProperty = \sprintf(
            'root.%s.%s',
            $summaryProperty,
            $partitionKeyProperty,
        );

        if ($higherPartition === null) {
            // if the partition is at the top level, return all top partitions
            // up to the partition
            yield $this->getSimpleQueryBuilder()->expr()->andX(
                $this->getSimpleQueryBuilder()->expr()->eq(
                    $levelProperty,
                    $partition->getLevel(),
                ),
                $this->getSimpleQueryBuilder()->expr()->lt(
                    $keyProperty,
                    $partition->getUpperBound(),
                ),
            );
        } elseif ($partition->getUpperBound() === $higherPartition->getUpperBound()) {
            // if the partition is at the end of the containing parent partition,
            // return the containing parent partition
            foreach ($this->getRangeConditions($higherPartition) as $condition) {
                yield $condition;
            }
        } else {
            // else return the range of the current level from the start of the
            // containing parent partition up to the end of the current
            // partition

            yield $this->getSimpleQueryBuilder()->expr()->andX(
                $this->getSimpleQueryBuilder()->expr()->eq(
                    $levelProperty,
                    $partition->getLevel(),
                ),
                $this->getSimpleQueryBuilder()->expr()->gte(
                    $keyProperty,
                    $higherPartition->getLowerBound(),
                ),
                $this->getSimpleQueryBuilder()->expr()->lt(
                    $keyProperty,
                    $partition->getUpperBound(),
                ),
            );

            // and then return the range of the previous of the parent partition

            $higherPrevious = $higherPartition->getPrevious();

            if ($higherPrevious !== null) {
                foreach ($this->getRangeConditions($higherPrevious) as $condition) {
                    yield $condition;
                }
            }
        }
    }

    private function addPartitionWhere(): bool
    {
        $maxId = $this->getLowestPartitionMaxId();

        if ($maxId === null) {
            return false;
        }

        $partitionClass = $this->metadata->getPartition()->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);
        $pointPartition = $partitionClass::createFromSourceValue($maxId, $lowestLevel);
        $conditions = $this->getRangeConditions($pointPartition);

        /** @psalm-suppress InvalidArgument */
        $orX = $this->getSimpleQueryBuilder()->expr()->orX(...$conditions);

        $this->getSimpleQueryBuilder()->andWhere($orX);

        return true;
    }

    private function getLowestPartitionMaxId(): int|string|null
    {
        $query = new LowestPartitionMaxIdQuery(
            entityManager: $this->entityManager,
            metadata: $this->metadata,
        );

        return $query->getLowestLevelPartitionMaxId();
    }

    private function addGroupingWhere(): void
    {
        $groupingsProperty = $this->metadata->getGroupingsProperty();
        $groupingsString = '';

        ksort($this->groupings);

        foreach ($this->groupings as $isGrouping) {
            $groupingsString .= $isGrouping ? '1' : '0';
        }

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                "root.%s = '%s'",
                $groupingsProperty,
                $groupingsString,
            ))
        ;
    }

    private function addUserSuppliedWhere(): void
    {
        $where = $this->query->getWhere();

        $validDimensions = array_values(array_filter(
            $this->metadata->getDimensionPropertyNames(),
            fn(string $dimension): bool => $dimension !== '@values',
        ));

        $visitor = new SummaryExpressionVisitor(
            queryBuilder: $this->getSimpleQueryBuilder(),
            validFields: $validDimensions,
        );

        foreach ($where as $whereExpression) {
            /** @psalm-suppress MixedAssignment */
            $expression = $visitor->dispatch($whereExpression);

            // @phpstan-ignore argument.type
            $this->getSimpleQueryBuilder()->andWhere($expression);
        }

        // add dimensions not in the query to the group by clause

        $involvedDimensions = $visitor->getInvolvedDimensions();
        $dimensionsInQuery = array_filter(
            $this->query->getGroupBy(),
            fn(string $dimension): bool => $dimension !== '@values',
        );
        $involvedDimensionNotInQuery = array_diff($involvedDimensions, $dimensionsInQuery);

        foreach ($involvedDimensionNotInQuery as $dimension) {
            if (str_contains($dimension, '.')) {
                [$dimensionProperty, $hierarchyProperty] = explode('.', $dimension);

                $dimensionMetadata = $this->metadata
                    ->getDimensionMetadata($dimensionProperty);

                $dimensionHierarchyMetadata = $dimensionMetadata->getHierarchy();

                if ($dimensionHierarchyMetadata === null) {
                    throw new UnexpectedValueException(\sprintf(
                        'Dimension "%s" is not hierarchical',
                        $dimensionProperty,
                    ));
                }

                $groupings = $dimensionHierarchyMetadata
                    ->getGroupingsByPropertyForSelect($hierarchyProperty);

                foreach ($groupings as $property => $isGrouping) {
                    if ($isGrouping !== false) {
                        continue;
                    }

                    $this->groupings[\sprintf('%s.%s', $dimensionProperty, $property)] = false;
                }

                continue;
            }

            $this->groupings[$dimension] = false;
        }
    }

    private function processAllDimensions(): void
    {
        $dimensionsInQuery = $this->query->getGroupBy();

        // add @values to the end of the dimensions if not present
        if (!\in_array('@values', $dimensionsInQuery, true)) {
            $dimensionsInQuery[] = '@values';
        }

        foreach ($dimensionsInQuery as $dimension) {
            $this->processDimension($dimension);
        }
    }

    private function processDimension(string $dimension): void
    {
        if ($dimension === '@values') {
            $this->addMeasuresToQueryBuilder();
        } elseif (str_contains($dimension, '.')) {
            $this->addHierarchicalDimensionToQueryBuilder($dimension);
        } else {
            $this->addNonHierarchicalDimensionToQueryBuilder($dimension);
        }
    }

    private function addHierarchicalDimensionToQueryBuilder(
        string $dimension,
    ): void {
        [$dimensionProperty, $hierarchyProperty] = explode('.', $dimension);

        if ($hierarchyProperty === '') {
            throw new UnexpectedValueException(\sprintf(
                'Invalid hierarchical dimension "%s".',
                $dimensionProperty,
            ));
        }

        // create alias

        $alias = 'e_' . hash('xxh128', $dimension);
        $this->dimensionAliases[$alias] = $dimension;

        // determine level

        $dimensionMetadata = $this->metadata
            ->getDimensionMetadata($dimensionProperty);

        $dimensionHierarchyMetadata = $dimensionMetadata->getHierarchy();

        if ($dimensionHierarchyMetadata === null) {
            throw new UnexpectedValueException(\sprintf(
                'Dimension "%s" is not hierarchical',
                $dimensionProperty,
            ));
        }

        // add where level clause

        $groupings = $dimensionHierarchyMetadata
            ->getGroupingsByPropertyForSelect($hierarchyProperty);

        foreach ($dimensionHierarchyMetadata->getProperties() as $property) {
            $key = \sprintf('%s.%s', $dimensionProperty, $property->getName());

            $this->groupings[$key] = $groupings[$property->getName()];
        }

        // add select

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                "root.%s.%s AS %s",
                $dimensionProperty,
                $hierarchyProperty,
                $alias,
            ))
        ;

        // add orderby

        $orderBy = $dimensionMetadata->getOrderBy();

        if (\is_array($orderBy)) {
            throw new MetadataException('orderBy cannot be an array for hierarchical dimension');
        }

        $this->getSimpleQueryBuilder()->addOrderBy(
            \sprintf(
                'root.%s.%s',
                $dimensionProperty,
                $hierarchyProperty,
            ),
            $orderBy->value,
        );

        // add group by and grouping fields

        $this->rollUpFields[] = $alias;

        $this->groupingFields[] = \sprintf(
            'root.%s.%s',
            $dimensionProperty,
            $hierarchyProperty,
        );
    }

    private function addMeasuresToQueryBuilder(): void
    {
        $measureMetadatas = $this->metadata->getMeasureMetadatas();

        foreach ($measureMetadatas as $key => $measureMetadata) {
            $function = $measureMetadata->getFirstFunction();
            $dql = $function->getSummaryToSummaryDQLFunction();

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf(
                    $dql . ' AS %s',
                    'root.' . $measureMetadata->getSummaryProperty(),
                    $key,
                ));
        }
    }

    private function addNonHierarchicalDimensionToQueryBuilder(
        string $dimension,
    ): void {
        $dimensionMetadata = $this->metadata->getDimensionMetadata($dimension);

        $classMetadata = ClassMetadataWrapper::get(
            manager: $this->entityManager,
            class: $this->metadata->getSummaryClass(),
        );

        try {
            $joinedEntityClass = $classMetadata
                ->getAssociationTargetClass($dimensionMetadata->getSummaryProperty());
            // @phpstan-ignore phpat.testPackageAnalyticsCore
        } catch (MappingException | \InvalidArgumentException) {
            $joinedEntityClass = null;
        }

        $this->groupings[$dimension] = false;

        if ($joinedEntityClass !== null) {
            // grouping by a related entity is not always possible, so we group
            // by its identifier instead, then we convert back to the object in
            // post

            $joinedClassMetadata = ClassMetadataWrapper::get(
                manager: $this->entityManager,
                class: $joinedEntityClass,
            );

            $identity = $joinedClassMetadata->getIdentifierFieldName();

            $dqlField = $this->resolvePath(\sprintf(
                '%s.%s',
                $dimension,
                $identity,
            ));

            // select

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf(
                    '%s AS %s',
                    $dqlField,
                    $dimension,
                ));

            // order by

            $orderBy = $dimensionMetadata->getOrderBy();

            if (!\is_array($orderBy)) {
                $this->getSimpleQueryBuilder()->addOrderBy($dqlField, $orderBy->value);
            } else {
                foreach ($orderBy as $orderField => $order) {
                    $dqlOrderField = $this->resolvePath(\sprintf(
                        '%s.%s',
                        $dimension,
                        $orderField,
                    ));

                    $alias = $dimension . '_' . $orderField;

                    $this->getSimpleQueryBuilder()
                        ->addSelect(\sprintf(
                            'MIN(%s) AS HIDDEN %s',
                            $dqlOrderField,
                            $alias,
                        ))
                        ->addOrderBy($alias, $order->value);
                }
            }

            // group by and grouping fields

            $this->rollUpFields[] = $dimension;
            $this->groupingFields[] = $dqlField;

            return;
        }

        // not joined

        $orderBy = $dimensionMetadata->getOrderBy();

        if (\is_array($orderBy)) {
            throw new MetadataException('orderBy cannot be an array for non-hierarchical dimension');
        }

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                'root.%s AS %s',
                $dimensionMetadata->getSummaryProperty(),
                $dimension,
            ))
            ->addOrderBy(
                \sprintf(
                    'root.%s',
                    $dimensionMetadata->getSummaryProperty(),
                ),
                $orderBy->value,
            )
        ;

        $this->rollUpFields[] = $dimension;
        $this->groupingFields[] = \sprintf(
            'root.%s',
            $dimensionMetadata->getSummaryProperty(),
        );
    }

    private function addUserSuppliedOrderBy(): void
    {
        $orderBy = $this->query->getOrderBy();

        if ($orderBy === []) {
            return;
        }

        $i = 0;

        foreach ($orderBy as $field => $order) {
            if ($this->metadata->isMeasure($field)) {
                $measureMetadata = $this->metadata->getMeasureMetadata($field);
                $function = $measureMetadata->getFirstFunction();
                $dql = $function->getSummaryToSummaryDQLFunction();

                $fieldString = \sprintf(
                    $dql,
                    'root.' . $measureMetadata->getSummaryProperty(),
                );
            } else {
                $fieldString = $this->resolvePath($field);
            }

            if ($i === 0) {
                $this->getSimpleQueryBuilder()->orderBy($fieldString, $order->value);
            } else {
                $this->getSimpleQueryBuilder()->addOrderBy($fieldString, $order->value);
            }

            $i++;
        }
    }
}
