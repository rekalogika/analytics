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
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Util\PartitionUtil;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * Roll up lower level summary to higher level by cubing the non-grouping row
 * of the lower level summary
 */
final class RollUpSummaryToSummaryCubingStrategyQuery extends AbstractQuery
{
    private readonly GroupBy $groupBy;

    /**
     * @var array<string,string>
     */
    private array $groupings = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->metadata->getSummaryClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->groupBy = new GroupBy();
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

        return $this->createSQL();
    }

    private function initialize(): void
    {
        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                "REKALOGIKA_NEXTVAL(%s)",
                $this->metadata->getSummaryClass(),
            ));
    }

    private function processPartition(): void
    {
        $partitionMetadata = $this->metadata->getPartition();
        $classifier = $partitionMetadata->getKeyClassifier();

        $valueResolver = new PropertyValueResolver(\sprintf(
            "%s.%s",
            $partitionMetadata->getSummaryProperty(),
            $partitionMetadata->getPartitionKeyProperty(),
        ));

        $function = $classifier->getDQL(
            input: $valueResolver,
            level: $this->start->getLevel(),
            context: new Context(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->metadata,
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
                $this->start->getLevel(),
            ))
        ;

        $this->groupBy->add(new Field('p_key'));
        $this->groupBy->add(new Field('p_level'));
    }

    private function processDimensions(): void
    {
        $i = 0;

        foreach ($this->metadata->getDimensions() as $levelProperty => $metadata) {
            $isEntity = $this->getSimpleQueryBuilder()
                ->getEntityManager()
                ->getClassMetadata($this->metadata->getSummaryClass())
                ->hasAssociation($levelProperty);

            $hierarchyMetadata = $metadata->getHierarchy();
            $summaryProperty = $metadata->getSummaryProperty();

            if ($hierarchyMetadata !== null) {
                $dimensionProperty = $metadata->getSummaryProperty();
                $dimensionPathsMetadata = $hierarchyMetadata->getPaths();

                $propertyToAlias = [];

                $groupingSet = new GroupingSet();

                // add a field set for all of the properties

                $fieldSet = new FieldSet();

                foreach ($hierarchyMetadata->getProperties() as $property) {
                    $name = $property->getName();
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

                        $rollUp->add($fieldSet);
                    }

                    $groupingSet->add($rollUp);
                }

                $this->groupBy->add($groupingSet);

                // add select for each of the properties

                foreach ($hierarchyMetadata->getProperties() as $property) {
                    $name = $property->getName();
                    $alias = $propertyToAlias[$name]
                        ?? throw new InvalidArgumentException(\sprintf(
                            'The alias for property "%s" is not defined',
                            $name,
                        ));

                    $this->getSimpleQueryBuilder()
                        ->addSelect(\sprintf(
                            'root.%s.%s AS %s',
                            $dimensionProperty,
                            $name,
                            $alias,
                        ));

                    $name = \sprintf('%s.%s', $summaryProperty, $name);
                    $this->groupings[$name] = \sprintf(
                        'root.%s.%s',
                        $dimensionProperty,
                        $name,
                    );
                }
            } elseif ($isEntity) {
                $alias = \sprintf('d%d_', $i++);

                $this->getSimpleQueryBuilder()
                    ->addSelect(\sprintf(
                        'IDENTITY(root.%s) AS %s',
                        $levelProperty,
                        $alias,
                    ));

                $cube = new Cube();
                $cube->add(new Field($alias));
                $this->groupBy->add($cube);

                $this->groupings[$summaryProperty] = \sprintf(
                    'IDENTITY(root.%s)',
                    $levelProperty,
                );
            } else {
                $alias = \sprintf('d%d_', $i++);

                $this->getSimpleQueryBuilder()
                    ->addSelect(\sprintf(
                        'root.%s AS %s',
                        $levelProperty,
                        $alias,
                    ));

                $cube = new Cube();
                $cube->add(new Field($alias));
                $this->groupBy->add($cube);

                $this->groupings[$summaryProperty] = \sprintf(
                    'root.%s',
                    $levelProperty,
                );
            }
        }
    }

    private function processMeasures(): void
    {
        foreach ($this->metadata->getMeasures() as $field => $metadata) {
            if ($metadata->isVirtual()) {
                continue;
            }

            $function = $metadata->getFunction();
            $function = reset($function);

            if (!$function instanceof AggregateFunction) {
                throw new InvalidArgumentException(\sprintf(
                    'The function "%s" is not an instance of AggregateFunction',
                    get_debug_type($function),
                ));
            }

            $function = $function->getAggregateToAggregateDQLExpression();

            if ($function === null) {
                continue;
            }

            $function = \sprintf(
                $function,
                \sprintf('root.%s', $field),
            );

            $this->getSimpleQueryBuilder()->addSelect($function);
        }
    }

    private function processConstraints(): void
    {
        // dimensions

        $groupingString = '';

        foreach ($this->metadata->getDimensions() as $dimensionMetadata) {
            $hierarchyMetadata = $dimensionMetadata->getHierarchy();

            // non hierarchical dimension

            if ($hierarchyMetadata === null) {
                $groupingString .= '0';

                continue;
            }

            // hierarchical dimension

            $properties = $hierarchyMetadata->getProperties();

            foreach ($properties as $property) {
                $groupingString .= '0';
            }
        }

        $partitionMetadata = $this->metadata->getPartition();
        $partitionProperty = $partitionMetadata->getSummaryProperty();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();
        $groupingsProperty = $this->metadata->getGroupingsProperty();

        $lowerBound = $this->start->getLowerBound();
        $upperBound = $this->end->getUpperBound();

        $lowerLevel = PartitionUtil::getLowerLevel($this->start);

        if ($lowerLevel === null) {
            throw new LogicException('The lowest level must be rolled up from the source');
        }

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                'root.%s.%s >= %d',
                $partitionProperty,
                $partitionKeyProperty,
                $lowerBound,
            ))
            ->andWhere(\sprintf(
                'root.%s.%s < %d',
                $partitionProperty,
                $partitionKeyProperty,
                $upperBound,
            ))
            ->andWhere(\sprintf(
                'root.%s.%s = %d',
                $partitionProperty,
                $partitionLevelProperty,
                $lowerLevel,
            ))
            ->andWhere(\sprintf(
                "root.%s = '%s'",
                $groupingsProperty,
                $groupingString,
            ))
        ;
    }

    private function processGroupings(): void
    {
        ksort($this->groupings);

        $this->getSimpleQueryBuilder()->addSelect(\sprintf(
            "REKALOGIKA_GROUPING_CONCAT(%s)",
            implode(', ', $this->groupings),
        ));
    }

    /**
     * @return iterable<string>
     */
    private function createSQL(): iterable
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();
        $this->groupBy->apply($query);
        $result = $query->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }
}
