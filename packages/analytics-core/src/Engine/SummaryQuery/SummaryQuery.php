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

namespace Rekalogika\Analytics\Engine\SummaryQuery;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Exception\QueryResultOverflowException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Expression\ExpressionUtil;
use Rekalogika\Analytics\Engine\Expression\SummaryExpressionVisitor;
use Rekalogika\Analytics\Engine\Groupings\Groupings;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;

final class SummaryQuery extends AbstractQuery
{
    /**
     * @var list<string>
     */
    private array $cubeFields = [];

    /**
     * This field is used for subtotal rollups, and is not related to the
     * groupings property below.
     *
     * @var list<string>
     */
    private array $groupingFields = [];

    private Groupings $groupings;

    /**
     * @var array<string,string>
     */
    private array $aliases = [];

    /**
     * @var list<array<string,mixed>>|null
     */
    private ?array $queryResult = null;

    /**
     * @phpstan-ignore missingType.generics
     */
    private Query $doctrineQuery;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly int|string $maxId,
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

        $this->groupings = Groupings::create($metadata);

        // add query builder parameters that are always used
        $this->initializeQueryBuilder();

        // add query parameters involving dimensions to the query builder
        $this->processAllDimensions();

        // add partition where clause
        $this->addPartitionWhere();

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

        $cube = new Cube();

        foreach ($this->cubeFields as $field) {
            $this->getSimpleQueryBuilder()->addGroupBy($field);
            $cube->add(new Field($field));
        }

        $groupBy = new GroupBy();
        $groupBy->add($cube);

        // create query & apply group by

        $this->doctrineQuery = $this->getSimpleQueryBuilder()->getQuery();

        if (\count($groupBy) > 0) {
            $groupBy->apply($this->doctrineQuery);
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

        // get result
        /** @var list<array<string,mixed>> */
        $result = $this->doctrineQuery->getArrayResult();

        // check safeguard

        if (\count($result) > $this->queryResultLimit) {
            throw new QueryResultOverflowException($this->queryResultLimit);
        }

        // change alias to dimension name

        $newResult = [];

        foreach ($result as $row) {
            $newRow = [];

            /** @var mixed $value */
            foreach ($row as $name => $value) {
                if (\array_key_exists($name, $this->aliases)) {
                    /** @psalm-suppress MixedAssignment */
                    $newRow[$this->aliases[$name]] = $value;
                } else {
                    /** @psalm-suppress MixedAssignment */
                    $newRow[$name] = $value;
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
    }

    /**
     * @return iterable<Comparison|Andx>
     */
    private function getRangeConditions(Partition $partition): iterable
    {
        $partitionLevelProperty = $this->metadata
            ->getPartition()
            ->getFullyQualifiedPartitionLevelProperty();

        $partitionKeyProperty = $this->metadata
            ->getPartition()
            ->getFullyQualifiedPartitionKeyProperty();

        $higherPartition = $partition->getContaining();

        $levelProperty = $this->resolve($partitionLevelProperty);
        $keyProperty = $this->resolve($partitionKeyProperty);

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

    private function addPartitionWhere(): void
    {
        $partitionClass = $this->metadata->getPartition()->getPartitionClass();
        $lowestLevel = PartitionUtil::getLowestLevel($partitionClass);
        $pointPartition = $partitionClass::createFromSourceValue($this->maxId, $lowestLevel);
        $conditions = $this->getRangeConditions($pointPartition);

        /** @psalm-suppress InvalidArgument */
        $orX = $this->getSimpleQueryBuilder()->expr()->orX(...$conditions);

        $this->getSimpleQueryBuilder()->andWhere($orX);
    }

    private function addGroupingWhere(): void
    {
        $groupingsProperty = $this->metadata->getGroupingsProperty();

        $groupingsString = $this->groupings
            ->getGroupingStringForSelect();

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                "%s = %s",
                $this->resolve($groupingsProperty),
                $this->getSimpleQueryBuilder()
                    ->createNamedParameter($groupingsString),
            ))
        ;
    }

    private function addUserSuppliedWhere(): void
    {
        $where = $this->query->getWhere();

        if ($where === null) {
            return;
        }

        $visitor = ExpressionUtil::addExpressionToQueryBuilder(
            metadata: $this->metadata,
            queryBuilder: $this->getSimpleQueryBuilder(),
            expression: $where,
            visitorClass: SummaryExpressionVisitor::class,
        );

        $involvedDimensions = $visitor->getInvolvedDimensions();

        $dimensionsInQuery = array_filter(
            $this->query->getGroupBy(),
            fn(string $dimension): bool => $dimension !== '@values',
        );

        $involvedDimensionNotInQuery = array_diff($involvedDimensions, $dimensionsInQuery);

        foreach ($involvedDimensionNotInQuery as $dimension) {
            $this->groupings->addSelected($dimension);
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
        } else {
            $this->addDimensionToQueryBuilder($dimension);
        }
    }

    private function addMeasuresToQueryBuilder(): void
    {
        $measureMetadatas = $this->metadata->getMeasures();

        foreach ($measureMetadatas as $name => $measureMetadata) {
            $summaryContext = SummaryQueryContext::create(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->metadata,
                measureMetadata: $measureMetadata,
            );

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf(
                    '%s AS %s',
                    $summaryContext->resolve($name),
                    $this->getAlias($name),
                ));
        }
    }

    private function getAlias(string $field): string
    {
        $alias = 'a_' . hash('xxh128', $field);

        $this->aliases[$alias] = $field;

        return $alias;
    }

    private function addDimensionToQueryBuilder(
        string $dimension,
    ): void {
        $dimensionMetadata = $this->metadata->getDimension($dimension);
        $alias = $this->getAlias($dimension);

        $classMetadata = new ClassMetadataWrapper(
            manager: $this->entityManager,
            class: $this->metadata->getSummaryClass(),
        );

        try {
            $joinedEntityClass = $classMetadata
                ->getAssociationTargetClass($dimensionMetadata->getName());
            // @phpstan-ignore phpat.testPackageAnalyticsCore
        } catch (MappingException | \InvalidArgumentException) {
            $joinedEntityClass = null;
        }

        // $this->groupings[$dimension] = false;
        $this->groupings->addSelected($dimension);

        if ($joinedEntityClass !== null) {
            // grouping by a related entity is not always possible, so we group
            // by its identifier instead, then we convert back to the object in
            // post

            $joinedClassMetadata = new ClassMetadataWrapper(
                manager: $this->entityManager,
                class: $joinedEntityClass,
            );

            $identity = $joinedClassMetadata->getIdentifierFieldName();

            $fieldExpression = $this->resolve(\sprintf(
                '%s.%s',
                $dimension,
                $identity,
            ));

            // select

            $this->getSimpleQueryBuilder()
                ->addSelect(\sprintf(
                    '%s AS %s',
                    $fieldExpression,
                    $alias,
                ));

            // order by

            $orderBy = $dimensionMetadata->getOrderBy();

            if (!\is_array($orderBy)) {
                $this->getSimpleQueryBuilder()->addOrderBy($fieldExpression, $orderBy->value);
            } else {
                foreach ($orderBy as $orderField => $order) {
                    $orderExpression = $this->resolve(\sprintf(
                        '%s.%s',
                        $dimension,
                        $orderField,
                    ));

                    $orderAlias = \sprintf(
                        '%s_%s',
                        $this->getAlias($dimension),
                        $orderField,
                    );

                    $this->getSimpleQueryBuilder()
                        ->addSelect(\sprintf(
                            'MIN(%s) AS HIDDEN %s',
                            $orderExpression,
                            $orderAlias,
                        ))
                        ->addOrderBy($orderAlias, $order->value);
                }
            }

            // group by and grouping fields

            $this->cubeFields[] = $alias;
            $this->groupingFields[] = $fieldExpression;

            return;
        }

        // not joined

        $orderBy = $dimensionMetadata->getOrderBy();

        if (\is_array($orderBy)) {
            throw new MetadataException('orderBy cannot be an array for non-hierarchical dimension');
        }

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                '%s AS %s',
                $this->resolve($dimensionMetadata->getName()),
                $alias,
            ))
            ->addOrderBy(
                $this->resolve($dimensionMetadata->getName()),
                $orderBy->value,
            )
        ;

        $this->cubeFields[] = $alias;

        $this->groupingFields[] =
            $this->resolve($dimensionMetadata->getName());
    }

    private function addUserSuppliedOrderBy(): void
    {
        $orderBy = $this->query->getOrderBy();

        if ($orderBy === []) {
            return;
        }

        $i = 0;

        foreach ($orderBy as $field => $order) {
            $propertyMetadata = $this->metadata->getProperty($field);

            if ($propertyMetadata instanceof MeasureMetadata) {
                $summaryContext = SummaryQueryContext::create(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->metadata,
                    measureMetadata: $this->metadata->getMeasure($field),
                );

                $fieldString = $summaryContext->resolve($field);
            } elseif (
                $propertyMetadata instanceof DimensionMetadata
            ) {
                $fieldString = $this->resolve($field);
            } else {
                throw new UnexpectedValueException(\sprintf(
                    'Field "%s" is not a valid dimension or measure.',
                    $field,
                ));
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
