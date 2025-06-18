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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\HasQueryBuilderModifier;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Engine\SummaryManager\PartitionManager\PartitionManager;
use Rekalogika\Analytics\Engine\SummaryManager\Query\Helper\Groupings;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class RollUpSourceToSummaryPerSourceQuery extends AbstractQuery
{
    private readonly GroupBy $groupBy;

    private Groupings $groupings;

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly PartitionManager $partitionManager,
        private readonly SummaryMetadata $summaryMetadata,
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->summaryMetadata->getSourceClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->groupBy = new GroupBy();
        $this->groupings = new Groupings();
    }

    /**
     * @return iterable<DecomposedQuery>
     */
    public function getQuery(): iterable
    {
        $this->initialize();
        $this->processPartition();
        $this->processDimensions();
        $this->processMeasures();
        $this->processConstraints();
        $this->processGroupings();
        $this->processQueryBuilderModifier();

        yield $this->createSqlStatement();
    }

    private function initialize(): void
    {
        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                "REKALOGIKA_NEXTVAL(%s)",
                $this->summaryMetadata->getSummaryClass(),
            ));
    }

    private function processPartition(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();
        $partitionClass = $partitionMetadata->getPartitionClass();
        $partitioningLevels = $partitionClass::getAllLevels();
        $lowestLevel = min($partitioningLevels);

        $function = $partitionClass::getClassifierExpression(
            input: $valueResolver,
            level: $lowestLevel,
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->summaryMetadata,
                partitionMetadata: $partitionMetadata,
            ),
        );

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                '%s AS p_key',
                $function,
            ))
            ->addSelect(\sprintf(
                '%s AS p_level',
                $lowestLevel,
            ));

        $this->groupBy->add(new Field('p_key'));
        $this->groupBy->add(new Field('p_level'));
    }

    private function processDimensions(): void
    {
        $i = 0;

        foreach ($this->summaryMetadata->getDimensions() as $dimensionMetadata) {
            $summaryProperty = $dimensionMetadata->getSummaryProperty();
            $dimensionHierarchyMetadata = $dimensionMetadata->getHierarchy();
            $valueResolver = $dimensionMetadata->getValueResolver();

            // if hierarchical
            if ($dimensionHierarchyMetadata !== null) {
                $groupingSet = new GroupingSet();
                $dimensionPathsMetadata = $dimensionHierarchyMetadata->getPaths();
                $propertyToAlias = [];

                // add a field set for all of the properties

                $fieldSet = new FieldSet();

                foreach ($dimensionHierarchyMetadata->getProperties() as $dimensionPropertyMetadata) {
                    $name = $dimensionPropertyMetadata->getName();
                    $alias = $propertyToAlias[$name] ??= \sprintf('d%d_', $i++);
                    $fieldSet->add(new Field($alias));
                }

                $groupingSet->add($fieldSet);

                // add rollup group by for each of the dimension paths

                foreach ($dimensionPathsMetadata as $dimensionPathMetadata) {
                    $rollUp = new RollUp();

                    foreach ($dimensionPathMetadata as $levelMetadata) {
                        $fieldSet = new FieldSet();

                        foreach ($levelMetadata as $propertyMetadata) {
                            $name = $propertyMetadata->getName();
                            $alias = $propertyToAlias[$name] ??= \sprintf('d%d_', $i++);
                            $fieldSet->add(new Field($alias));
                        }

                        if (\count($fieldSet) === 1) {
                            $rollUp->add($fieldSet->toArray()[0]);
                        } else {
                            $rollUp->add($fieldSet);
                        }
                    }

                    $groupingSet->add($rollUp);
                }

                $this->groupBy->add($groupingSet);

                // add select for each of the properties

                foreach ($dimensionHierarchyMetadata->getProperties() as $dimensionPropertyMetadata) {
                    $name = $dimensionPropertyMetadata->getName();
                    $fullyQualifiedName = \sprintf('%s.%s', $summaryProperty, $name);

                    $dimensionPropertyMetadata = $this->summaryMetadata
                        ->getDimensionProperty($fullyQualifiedName);

                    $alias = $propertyToAlias[$name]
                        ?? throw new InvalidArgumentException(\sprintf(
                            'Alias for property "%s" not found.',
                            $name,
                        ));

                    $hierarchyAwareValueResolver = $dimensionPropertyMetadata->getValueResolver();

                    $expression = $hierarchyAwareValueResolver
                        ->withInput($valueResolver)
                        ->getExpression(
                            context: new SourceQueryContext(
                                queryBuilder: $this->getSimpleQueryBuilder(),
                                summaryMetadata: $this->summaryMetadata,
                                dimensionMetadata: $dimensionMetadata,
                                dimensionPropertyMetadata: $dimensionPropertyMetadata,
                            ),
                        );

                    $this->getSimpleQueryBuilder()
                        ->addSelect(\sprintf(
                            '%s AS %s',
                            $expression,
                            $alias,
                        ));

                    $this->groupings->add(
                        property: $fullyQualifiedName,
                        expression: $expression,
                    );
                }
            } else {
                // if not hierarchical

                $expression = $valueResolver->getExpression(
                    context: new SourceQueryContext(
                        queryBuilder: $this->getSimpleQueryBuilder(),
                        summaryMetadata: $this->summaryMetadata,
                        dimensionMetadata: $dimensionMetadata,
                    ),
                );

                $alias = \sprintf('d%d_', $i++);

                $this->getSimpleQueryBuilder()
                    ->addSelect(\sprintf('%s AS %s', $expression, $alias));

                $cube = new Cube();
                $cube->add(new Field($alias));
                $this->groupBy->add($cube);

                $this->groupings->add(
                    property: $summaryProperty,
                    expression: $expression,
                );
            }
        }
    }

    private function processMeasures(): void
    {
        foreach ($this->summaryMetadata->getMeasures() as $measureMetadata) {
            if ($measureMetadata->isVirtual()) {
                continue;
            }

            $function = $measureMetadata->getFunction();

            if (!$function instanceof SummarizableAggregateFunction) {
                continue;
            }

            $expression = $function->getSourceToAggregateExpression(
                context: new SourceQueryContext(
                    queryBuilder: $this->getSimpleQueryBuilder(),
                    summaryMetadata: $this->summaryMetadata,
                    measureMetadata: $measureMetadata,
                ),
            );

            $this->getSimpleQueryBuilder()->addSelect($expression);
        }
    }

    private function processConstraints(): void
    {
        $partitionMetadata = $this->summaryMetadata->getPartition();
        $valueResolver = $partitionMetadata->getSource();

        /** @psalm-suppress MixedAssignment */
        $start = $this->partitionManager
            ->calculateSourceBoundValueFromPartition($this->start, 'lower');

        /** @psalm-suppress MixedAssignment */
        $end = $this->partitionManager
            ->calculateSourceBoundValueFromPartition($this->end, 'upper');

        // add constraints

        $properties = $valueResolver->getInvolvedProperties();

        if (\count($properties) !== 1) {
            throw new UnexpectedValueException(\sprintf(
                'Expected exactly one property, got %d',
                \count($properties),
            ));
        }

        $property = $properties[0];

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                "%s >= %s",
                $this->resolve($property),
                $this->getSimpleQueryBuilder()->createNamedParameter($start),
            ))
            ->andWhere(\sprintf(
                "%s < %s",
                $this->resolve($property),
                $this->getSimpleQueryBuilder()->createNamedParameter($end),
            ));
    }

    private function processQueryBuilderModifier(): void
    {
        $class = $this->summaryMetadata->getSummaryClass();

        if (is_a($class, HasQueryBuilderModifier::class, true)) {
            $class::modifyQueryBuilder(
                $this->getSimpleQueryBuilder()->getQueryBuilder(),
            );
        }
    }

    private function processGroupings(): void
    {
        $this->getSimpleQueryBuilder()->addSelect(\sprintf(
            "REKALOGIKA_GROUPING_CONCAT(%s)",
            $this->groupings->getExpression(),
        ));
    }

    private function createSqlStatement(): DecomposedQuery
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();
        $this->groupBy->apply($query);

        return DecomposedQuery::createFromQuery($query);
    }
}
