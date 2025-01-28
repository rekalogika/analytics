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

use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\DimensionValueResolverContext;
use Rekalogika\Analytics\HasQueryBuilderModifier;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Partition;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class RollUpSourceToSummaryPerSourceQuery extends AbstractQuery
{
    private readonly GroupBy $groupBy;

    /**
     * @var list<string>
     */
    private array $groupings = [];

    /**
     * @param class-string $sourceClass
     */
    public function __construct(
        private readonly string $sourceClass,
        private readonly QueryBuilder $queryBuilder,
        private readonly SummaryMetadata $metadata,
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        parent::__construct($queryBuilder);

        $this->groupBy = new GroupBy();
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @return iterable<string>
     */
    public function getSQL(): iterable
    {
        $this->initialize();
        $this->processPartition();
        $this->processDimensions();
        $this->processMeasures();
        $this->processConstraints();
        $this->processGroupings();
        $this->processQueryBuilderModifier();

        return $this->createSqlStatement();
    }

    private function initialize(): void
    {
        $this->queryBuilder
            ->from($this->sourceClass, 'root')
            ->addSelect(\sprintf(
                "REKALOGIKA_NEXTVAL(%s)",
                $this->metadata->getSummaryClass(),
            ));
    }

    private function processPartition(): void
    {
        $partitionMetadata = $this->metadata->getPartition();

        $valueResolver = $partitionMetadata->getSource()[$this->sourceClass]
            ?? throw new \RuntimeException('Value resolver not found');

        $partitionClass = $partitionMetadata->getPartitionClass();
        $partitioningLevels = $partitionClass::getAllLevels();
        $lowestLevel = min($partitioningLevels);

        $classifier = $partitionMetadata->getKeyClassifier();

        $function = $classifier->getDQL(
            input: $valueResolver,
            level: $lowestLevel,
            context: $this->getQueryContext(),
        );

        $this->queryBuilder
            ->addSelect(\sprintf(
                '%s AS p_key',
                $function,
            ))
            ->addSelect(\sprintf(
                '%s AS p_level',
                $lowestLevel,
            ));

        $this->groupBy->addItem(new Field('p_key'));
        $this->groupBy->addItem(new Field('p_level'));
    }

    private function processDimensions(): void
    {
        $i = 0;

        foreach ($this->metadata->getDimensionMetadatas() as $metadata) {
            $hierarchyMetadata = $metadata->getHierarchy();

            $valueResolver = $metadata->getSource()[$this->sourceClass]
                ?? throw new \RuntimeException('Value resolver not found');

            $propertySqlField = $valueResolver->getDQL($this->getQueryContext());

            // if hierarchical
            if ($hierarchyMetadata !== null) {
                $groupingSet = new GroupingSet();
                $dimensionPathsMetadata = $hierarchyMetadata->getPaths();
                $propertyToAlias = [];

                // add a field set for all of the properties

                $fieldSet = new FieldSet();

                foreach ($hierarchyMetadata->getProperties() as $property) {
                    $name = $property->getName();
                    $alias = $propertyToAlias[$name] ??= \sprintf('d%d_', $i++);
                    $fieldSet->addField(new Field($alias));
                }

                $groupingSet->addItem($fieldSet);

                // add rollup group by for each of the dimension paths

                foreach ($dimensionPathsMetadata as $dimensionPathMetadata) {
                    $rollUp = new RollUp();

                    foreach ($dimensionPathMetadata as $levelMetadata) {
                        $fieldSet = new FieldSet();

                        foreach ($levelMetadata as $propertyMetadata) {
                            $name = $propertyMetadata->getName();
                            $alias = $propertyToAlias[$name] ??= \sprintf('d%d_', $i++);
                            $fieldSet->addField(new Field($alias));
                        }

                        if (\count($fieldSet) === 1) {
                            $rollUp->addField($fieldSet->toArray()[0]);
                        } else {
                            $rollUp->addField($fieldSet);
                        }
                    }

                    $groupingSet->addItem($rollUp);
                }

                $this->groupBy->addItem($groupingSet);

                // add select for each of the properties

                foreach ($hierarchyMetadata->getProperties() as $property) {
                    $name = $property->getName();

                    $alias = $propertyToAlias[$name]
                        ?? throw new \RuntimeException('Alias not found');

                    $valueResolver = $property->getValueResolver();

                    $dimensionValueResolverContext = new DimensionValueResolverContext(
                        queryContext: $this->getQueryContext(),
                        propertyMetadata: $property,
                    );

                    $function = $valueResolver->getDQL(
                        input: $propertySqlField,
                        context: $dimensionValueResolverContext,
                    );

                    $this->queryBuilder
                        ->addSelect(\sprintf(
                            '%s AS %s',
                            $function,
                            $alias,
                        ));

                    $this->groupings[] = $function;
                }
            } else {
                // if not hierarchical

                $alias = \sprintf('d%d_', $i++);

                $this->queryBuilder
                    ->addSelect(\sprintf('%s AS %s', $propertySqlField, $alias));

                $cube = new Cube();
                $cube->addField(new Field($alias));
                $this->groupBy->addItem($cube);

                $this->groupings[] = $propertySqlField;
            }
        }
    }

    private function processMeasures(): void
    {
        foreach ($this->metadata->getMeasureMetadatas() as $metadata) {
            $function = $metadata->getFunction()[$this->sourceClass]
                ?? throw new \RuntimeException('Function not found');

            $function = $function
                ->getSourceToSummaryDQLFunction($this->getQueryContext());

            $this->queryBuilder->addSelect($function);
        }
    }

    private function processConstraints(): void
    {
        $partitionMetadata = $this->metadata->getPartition();

        $valueResolver = $partitionMetadata->getSource()[$this->sourceClass]
            ?? throw new \RuntimeException('Value resolver not found');

        $start = $this->metadata
            ->calculateSourceBoundValueFromPartition($this->start, 'lower');

        $end = $this->metadata
            ->calculateSourceBoundValueFromPartition($this->end, 'upper');

        // add constraints

        $properties = $valueResolver->getInvolvedProperties();

        if (\count($properties) !== 1) {
            throw new \RuntimeException('Value resolver for a partition must have exactly one property');
        }

        $property = $properties[0];

        $this->queryBuilder
            ->andWhere(\sprintf(
                "%s >= '%s'",
                $this->resolvePath($property),
                $start,
            ))
            ->andWhere(\sprintf(
                "%s < '%s'",
                $this->resolvePath($property),
                $end,
            ));
    }

    private function processQueryBuilderModifier(): void
    {
        $class = $this->metadata->getSummaryClass();

        if (is_a($class, HasQueryBuilderModifier::class, true)) {
            $class::modifyQueryBuilder($this->queryBuilder);
        }
    }

    private function processGroupings(): void
    {
        $this->queryBuilder->addSelect(\sprintf(
            "REKALOGIKA_GROUPING_CONCAT(%s)",
            implode(', ', $this->groupings),
        ));
    }

    /**
     * @return iterable<string>
     */
    private function createSqlStatement(): iterable
    {
        $query = $this->queryBuilder->getQuery();
        $this->groupBy->apply($query);
        $result = $query->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }
}
