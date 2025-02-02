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
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\Query\SummaryResult;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ArrayToTreeTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\MeasureSorter;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ResultResolver;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ResultToDimensionTableTransformer;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\UnpivotValuesTransformer;
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

    private readonly string $summaryProperty;

    private readonly string $partitionLevelProperty;

    private readonly string $partitionKeyProperty;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly SummaryQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
        parent::__construct($queryBuilder);

        $this->entityManager = $this->queryBuilder->getEntityManager();

        $this->summaryProperty = $this->metadata->getPartition()
            ->getSummaryProperty();

        $this->partitionLevelProperty = $this->metadata->getPartition()
            ->getPartitionLevelProperty();

        $this->partitionKeyProperty = $this->metadata->getPartition()
            ->getPartitionKeyProperty();
    }

    public function execute(): SummaryResult
    {
        // add query builder parameters that are always used
        $this->initializeQueryBuilder();

        // add query parameters involving dimensions to the query builder
        $this->processAllDimensions();

        // apply filter
        // @todo restore functionality
        // $this->applyFilter();

        // add partition where clause
        $this->addPartitionWhere();

        // add grouping where clause
        $this->addGroupingWhere();

        // check if select is empty
        if (empty($this->queryBuilder->getDQLPart('select'))) {
            return new SummaryResult(
                items: [],
            );
        }

        // execute doctrine query
        $result = $this->getResult();

        // resolve result, convert id to entity, and use user-provided getters in
        // the summary entity to normalize the result

        $resultResolver = new ResultResolver(
            propertyAccessor: $this->propertyAccessor,
            metadata: $this->metadata,
            entityManager: $this->entityManager,
        );

        $result = $resultResolver->resolveResult($result);

        // unpivot result

        $unpivotTransformer = new UnpivotValuesTransformer(
            summaryQuery: $this->query,
        );

        $result = $unpivotTransformer->unpivot($result);

        // sort measures

        $measureSorter = new MeasureSorter(
            summaryQuery: $this->query,
            metadata: $this->metadata,
        );

        $result = $measureSorter->sortMeasures($result);

        // wrap resulting values using our dimension and measure classes

        $resultToDimensionTableTransformer =
            new ResultToDimensionTableTransformer(
                metadata: $this->metadata,
            );

        $result = $resultToDimensionTableTransformer
            ->transformResultToDimensionTable($result);

        // convert the tabular format to a tree format

        $arrayToTreeTransformer = new ArrayToTreeTransformer();
        $result = $arrayToTreeTransformer->arrayToTree($result);

        // wrap the result using our SummaryResult class

        return new SummaryResult(items: $result);
    }

    private function initializeQueryBuilder(): void
    {
        $summaryClass = $this->metadata->getSummaryClass();

        $this->queryBuilder->from($summaryClass, 'root');

        foreach ($this->metadata->getDimensionPropertyNames() as $propertyName) {
            $this->groupings[$propertyName] = true;
        }
    }

    // private function applyFilter(): void
    // {
    //     $filters = $this->query->getFilters();
    //     $dimensionIds = array_keys($this->metadata->getDimensionMetadatas());
    //     $i = 1;
    //     foreach ($filters as $filter) {
    //         $filterDimension = $filter->getDimension();
    //         $filterEqualTo = $filter->getEqualTo();
    //         if (!\in_array($filterDimension, $dimensionIds, true)) {
    //             throw new \InvalidArgumentException(\sprintf('Invalid dimension "%s"', $filterDimension));
    //         }
    //         $this->queryBuilder
    //             ->andWhere(\sprintf('root.%s = :filterValue%d', $filterDimension, $i))
    //             ->setParameter(\sprintf('filterValue%d', $i), $filterEqualTo);
    //         $this->groupings[$filterDimension] = false;
    //         $i++;
    //     }
    // }
    /**
     * @return iterable<Comparison|Andx>
     */
    private function getRangeConditions(Partition $partition): iterable
    {
        $higherPartition = $partition->getContaining();

        $levelProperty = \sprintf(
            'root.%s.%s',
            $this->summaryProperty,
            $this->partitionLevelProperty,
        );

        $keyProperty = \sprintf(
            'root.%s.%s',
            $this->summaryProperty,
            $this->partitionKeyProperty,
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

    private function addPartitionWhere(): void
    {
        $maxId = $this->getLowestPartitionMaxId();
        $partitionClass = $this->metadata->getPartition()->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);
        $pointPartition = $partitionClass::createFromSourceValue($maxId, $lowestLevel);
        $conditions = $this->getRangeConditions($pointPartition);

        /** @psalm-suppress InvalidArgument */
        $orX = $this->queryBuilder->expr()->orX(...$conditions);

        $this->queryBuilder->andWhere($orX);

        // $partitionClass = $this->metadata->getPartition()->getPartitionClass();
        // $highestLevel = PartitionUtil::getHighestLevel($partitionClass);

        // $partitionMetadata = $this->metadata->getPartition();
        // $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        // $this->queryBuilder
        //     ->andWhere(\sprintf(
        //         'root.partition.%s = %d',
        //         $partitionLevelProperty,
        //         $highestLevel,
        //     ));
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

        foreach ($this->groupings as $isGrouping) {
            $groupingsString .= $isGrouping ? '1' : '0';
        }

        $this->queryBuilder
            ->andWhere(\sprintf(
                "root.%s = '%s'",
                $groupingsProperty,
                $groupingsString,
            ));
    }

    private function processAllDimensions(): void
    {
        $dimensionsInQuery = $this->query->getGroupBy();

        // add @values if not present
        if (!\in_array('@values', $dimensionsInQuery, true)) {
            $dimensionsInQuery[] = '@values';
        }

        foreach ($dimensionsInQuery as $dimension) {
            if ($dimension === '@values') {
                $this->addValuesToQueryBuilder($this->query->getSelect());
            } elseif (str_contains($dimension, '.')) {
                $this->addHierarchicalDimensionToQueryBuilder($dimension);
            } else {
                $this->addNonHierarchicalDimensionToQueryBuilder($dimension);
            }
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

        // add select and order by clause

        $this->queryBuilder
            ->addSelect(\sprintf(
                "root.%s.%s AS %s",
                $dimensionProperty,
                $hierarchyProperty,
                $alias,
            ))
            ->addOrderBy(\sprintf(
                'root.%s.%s',
                $dimensionProperty,
                $hierarchyProperty,
            ))
        ;

        $this->rollUpFields[] = $alias;

        $this->groupingFields[] = \sprintf(
            'root.%s.%s',
            $dimensionProperty,
            $hierarchyProperty,
        );
    }

    /**
     * @param list<string> $values
     */
    private function addValuesToQueryBuilder(
        array $values,
    ): void {
        foreach ($values as $value) {
            $measureMetadata = $this->metadata->getMeasureMetadata($value);
            $functions = $measureMetadata->getFunction();
            $function = reset($functions);

            if ($function === false) {
                throw new \InvalidArgumentException(\sprintf('Measure %s has no function', $value));
            }

            $dql = $function->getSummaryToSummaryDQLFunction();

            $this->queryBuilder
                ->addSelect(\sprintf(
                    $dql . ' AS %s',
                    'root.' . $measureMetadata->getSummaryProperty(),
                    $value,
                ));
        }
    }

    private function addNonHierarchicalDimensionToQueryBuilder(string $dimension): void
    {
        $dimensionMetadata = $this->metadata->getDimensionMetadata($dimension);

        $classMetadata = $this->entityManager
            ->getClassMetadata($this->metadata->getSummaryClass());

        try {
            $joinedEntityClass = $classMetadata
                ->getAssociationMapping($dimensionMetadata->getSummaryProperty())['targetEntity'];
        } catch (MappingException) {
            $joinedEntityClass = null;
        }

        $this->groupings[$dimension] = false;

        if ($joinedEntityClass !== null) {
            $alias = 'e_' . hash('xxh128', $dimension);

            $joinedClassMetadata = $this->entityManager
                ->getClassMetadata($joinedEntityClass);

            $identity = $joinedClassMetadata->getSingleIdentifierFieldName();

            $this->queryBuilder
                ->leftJoin(
                    $joinedEntityClass,
                    $alias,
                    'WITH',
                    \sprintf(
                        'root.%s = %s',
                        $dimensionMetadata->getSummaryProperty(),
                        $alias,
                    ),
                )
                ->addSelect(\sprintf(
                    '%s.%s AS %s',
                    $alias,
                    $identity,
                    $dimension,
                ))
                ->addOrderBy(\sprintf(
                    '%s.%s',
                    $alias,
                    $identity,
                ))
            ;

            $this->rollUpFields[] = $dimension;
            $this->groupingFields[] = \sprintf(
                '%s.%s',
                $alias,
                $identity,
            );

            return;
        }

        $this->queryBuilder
            ->addSelect(\sprintf(
                'root.%s AS %s',
                $dimensionMetadata->getSummaryProperty(),
                $dimension,
            ))
            ->addOrderBy(\sprintf(
                'root.%s',
                $dimensionMetadata->getSummaryProperty(),
            ))
        ;

        $this->rollUpFields[] = $dimension;
        $this->groupingFields[] = \sprintf(
            'root.%s',
            $dimensionMetadata->getSummaryProperty(),
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function getResult(): array
    {
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

        return $newResult;
    }
}
