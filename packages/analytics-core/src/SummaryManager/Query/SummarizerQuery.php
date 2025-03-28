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
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\NormalTableToTreeTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultNormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTree;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\QueryResultToTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\TableToNormalTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\Util\PartitionUtil;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class SummarizerQuery extends AbstractQuery
{
    private readonly EntityManagerInterface $entityManager;

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

    private ?DefaultTable $table = null;

    private ?DefaultNormalTable $normalTable = null;

    private ?DefaultTree $tree = null;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly SummaryQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
        parent::__construct($queryBuilder);

        $this->entityManager = $this->queryBuilder->getEntityManager();

        $dimensionsInQuery = $this->query->getGroupBy();

        if (!\in_array('@values', $dimensionsInQuery, true)) {
            $dimensionsInQuery[] = '@values';
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function getQueryResult(): array
    {
        if ($this->queryResult !== null) {
            return $this->queryResult;
        }

        // check if select is empty
        if ($this->query->getSelect() === []) {
            return [];
        }

        // add query builder parameters that are always used
        $this->initializeQueryBuilder();

        // add query parameters involving dimensions to the query builder
        $this->processAllDimensions();

        // add partition where clause
        if (!$this->addPartitionWhere()) {
            return [];
        }

        // add where clause supplied by the user
        $this->addUserSuppliedWhere();

        // add grouping where clause
        $this->addGroupingWhere();

        // add order by clause supplied by the user
        $this->addUserSuppliedOrderBy();

        // create grouping field

        $this->queryBuilder->addSelect(\sprintf(
            "REKALOGIKA_GROUPING_CONCAT(%s) AS __grouping",
            implode(', ', $this->groupingFields),
        ));

        // create group by

        $rollUp = new RollUp();

        foreach ($this->rollUpFields as $field) {
            // $this->queryBuilder->addGroupBy($field);
            $rollUp->add(new Field($field));
        }

        $groupBy = new GroupBy();
        $groupBy->add($rollUp);

        // create query & apply group by

        $query = $this->queryBuilder->getQuery();

        if (\count($groupBy) > 0) {
            $groupBy->apply($query);
        }

        // get result
        /** @var list<array<string,mixed>> */
        $result = $query->getArrayResult();

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

    public function getTable(): DefaultTable
    {
        return $this->table ??= QueryResultToTableTransformer::transform(
            query: $this->query,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
            propertyAccessor: $this->propertyAccessor,
            input: $this->getQueryResult(),
        );
    }

    public function getNormalTable(): DefaultNormalTable
    {
        return $this->normalTable ??= TableToNormalTableTransformer::transform(
            summaryQuery: $this->query,
            metadata: $this->metadata,
            input: $this->getTable(),
        );
    }

    public function getTree(): DefaultTree
    {
        return $this->tree ??= NormalTableToTreeTransformer::transform(
            normalTable: $this->getNormalTable(),
            type: $this->hasTieredOrder() ? 'tree' : 'table',
        );
    }

    private function hasTieredOrder(): bool
    {
        $orderBy = $this->query->getOrderBy();

        if (\count($orderBy) === 0) {
            return true;
        }

        $orderFields = array_keys($orderBy);

        $dimensionWithoutValues = array_filter(
            $this->metadata->getDimensionPropertyNames(),
            fn(string $dimension): bool => $dimension !== '@values',
        );

        return $orderFields === $dimensionWithoutValues;
    }

    private function initializeQueryBuilder(): void
    {
        $summaryClass = $this->metadata->getSummaryClass();

        $this->queryBuilder->from($summaryClass, 'root');

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
            yield $this->queryBuilder->expr()->andX(
                $this->queryBuilder->expr()->eq(
                    $levelProperty,
                    $partition->getLevel(),
                ),
                $this->queryBuilder->expr()->lt(
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

            yield $this->queryBuilder->expr()->andX(
                $this->queryBuilder->expr()->eq(
                    $levelProperty,
                    $partition->getLevel(),
                ),
                $this->queryBuilder->expr()->gte(
                    $keyProperty,
                    $higherPartition->getLowerBound(),
                ),
                $this->queryBuilder->expr()->lt(
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
        $orX = $this->queryBuilder->expr()->orX(...$conditions);

        $this->queryBuilder->andWhere($orX);

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

        $this->queryBuilder
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
            queryBuilder: $this->queryBuilder,
            validFields: $validDimensions,
            queryContext: $this->getQueryContext(),
        );

        foreach ($where as $whereExpression) {
            /** @psalm-suppress MixedAssignment */
            $expression = $visitor->dispatch($whereExpression);

            // @phpstan-ignore argument.type
            $this->queryBuilder->andWhere($expression);
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
                    throw new \InvalidArgumentException(\sprintf('Dimension %s is not hierarchical', $dimensionProperty));
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
            throw new \InvalidArgumentException(\sprintf('Invalid hierarchical dimension: %s', $dimensionProperty));
        }

        // create alias

        $alias = 'e_' . hash('xxh128', $dimension);
        $this->dimensionAliases[$alias] = $dimension;

        // determine level

        $dimensionMetadata = $this->metadata
            ->getDimensionMetadata($dimensionProperty);

        $dimensionHierarchyMetadata = $dimensionMetadata->getHierarchy();

        if ($dimensionHierarchyMetadata === null) {
            throw new \InvalidArgumentException(\sprintf('Dimension %s is not hierarchical', $dimensionProperty));
        }

        // add where level clause

        $groupings = $dimensionHierarchyMetadata
            ->getGroupingsByPropertyForSelect($hierarchyProperty);

        foreach ($dimensionHierarchyMetadata->getProperties() as $property) {
            $key = \sprintf('%s.%s', $dimensionProperty, $property->getName());

            $this->groupings[$key] = $groupings[$property->getName()];
        }

        // add select

        $this->queryBuilder
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
            throw new \InvalidArgumentException('orderBy cannot be an array for hierarchical dimension');
        }

        $this->queryBuilder->addOrderBy(
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

        foreach ($measureMetadatas as $value => $measureMetadata) {
            $function = $measureMetadata->getFirstFunction();
            $dql = $function->getSummaryToSummaryDQLFunction();

            $this->queryBuilder
                ->addSelect(\sprintf(
                    $dql . ' AS %s',
                    'root.' . $measureMetadata->getSummaryProperty(),
                    $value,
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

            $dqlField = $this->getQueryContext()->resolvePath(\sprintf(
                '%s.%s',
                $dimension,
                $identity,
            ));

            // select

            $this->queryBuilder
                ->addSelect(\sprintf(
                    '%s AS %s',
                    $dqlField,
                    $dimension,
                ));

            // order by

            $orderBy = $dimensionMetadata->getOrderBy();

            if (!\is_array($orderBy)) {
                $this->queryBuilder->addOrderBy($dqlField, $orderBy->value);
            } else {
                foreach ($orderBy as $orderField => $order) {
                    $dqlOrderField = $this->getQueryContext()
                        ->resolvePath(\sprintf(
                            '%s.%s',
                            $dimension,
                            $orderField,
                        ));

                    $alias = $dimension . '_' . $orderField;

                    $this->queryBuilder
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
            throw new \InvalidArgumentException('orderBy cannot be an array for non-hierarchical dimension');
        }

        $this->queryBuilder
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
                $fieldString = $this->getQueryContext()->resolvePath($field);
            }

            if ($i === 0) {
                $this->queryBuilder->orderBy($fieldString, $order->value);
            } else {
                $this->queryBuilder->addOrderBy($fieldString, $order->value);
            }

            $i++;
        }
    }
}
