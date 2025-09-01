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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Exception\QueryResultOverflowException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;
use Rekalogika\Analytics\Engine\Groupings\Groupings;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultQuery;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\ExpressionUtil;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\SummaryExpressionVisitor;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\PartitionExpressionResolver;
use Rekalogika\Analytics\Metadata\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\MeasureMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;

final class SummaryQuery extends AbstractQuery
{
    private Cube $cube;

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

    private PartitionExpressionResolver $partitionExpressionResolver;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DefaultQuery $query,
        private readonly SummaryMetadata $metadata,
        private readonly int|string $maxId,
        private readonly int $queryResultLimit,
    ) {
        $summaryClass = $metadata->getSummaryClass();
        $this->cube = new Cube();

        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $summaryClass,
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $dimensionsInQuery = $this->query->getDimensions();

        if (!\in_array('@values', $dimensionsInQuery, true)) {
            $dimensionsInQuery[] = '@values';
        }

        $this->groupings = Groupings::create($metadata);

        // initialize range conditions resolver
        $this->initializeRangeConditionsResolver();

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

        $groupBy = new GroupBy($this->cube);

        $this->doctrineQuery = $this->getSimpleQueryBuilder()->getQuery();

        if (\count($this->cube) > 0) {
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

    private function initializeRangeConditionsResolver(): void
    {
        $partitionLevelProperty = $this->metadata
            ->getPartition()
            ->getFullyQualifiedPartitionLevelProperty();

        $partitionKeyProperty = $this->metadata
            ->getPartition()
            ->getFullyQualifiedPartitionKeyProperty();

        $levelProperty = $this->resolve($partitionLevelProperty);
        $keyProperty = $this->resolve($partitionKeyProperty);

        $this->partitionExpressionResolver = new PartitionExpressionResolver(
            levelProperty: $levelProperty,
            keyProperty: $keyProperty,
            partitionClass: $this->metadata->getPartition()->getPartitionClass(),
        );
    }

    private function addPartitionWhere(): void
    {
        $conditions = $this->partitionExpressionResolver
            ->resolvePartitionExpression($this->maxId);

        $this->getSimpleQueryBuilder()->addCriteria($conditions);
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
        $where = $this->query->getDice();

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
            $this->query->getDimensions(),
            fn(string $dimension): bool => $dimension !== '@values',
        );

        $involvedDimensionNotInQuery = array_diff($involvedDimensions, $dimensionsInQuery);

        foreach ($involvedDimensionNotInQuery as $dimension) {
            $this->groupings->addSelected($dimension);
        }
    }

    private function processAllDimensions(): void
    {
        $dimensionsInQuery = $this->query->getDimensions();

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
            $function = $measureMetadata->getFunction();

            if ($function instanceof PseudoMeasure) {
                // virtual measure, skip
                continue;
            }

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
            $fieldSet = new FieldSet(new Field($alias));

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
                            '%s AS HIDDEN %s',
                            $orderExpression,
                            $orderAlias,
                        ))
                        ->addOrderBy($orderAlias, $order->value);

                    $fieldSet->add(new Field($orderAlias));
                }
            }

            // group by and grouping fields
            $this->groupingFields[] = $fieldExpression;
            $this->cube->add($fieldSet);

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

        $this->cube->add(new Field($alias));

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
