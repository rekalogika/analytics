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
use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\Partition;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Util\PartitionUtil;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;

/**
 * Roll up lower level summary to higher level by grouping by the entire row set
 */
final class RollUpSummaryToSummaryGroupAllStrategyQuery extends AbstractQuery
{
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly SummaryMetadata $metadata,
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        parent::__construct($queryBuilder);
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

        return $this->createSQL();
    }

    private function initialize(): void
    {
        $this->queryBuilder
            ->from($this->metadata->getSummaryClass(), 'root')
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
            context: $this->getQueryContext(),
        );

        $this->queryBuilder
            ->addSelect(\sprintf(
                'MIN(%s) AS p_key',
                $function,
            ))
            ->addSelect(\sprintf(
                '%s AS p_level',
                $this->start->getLevel(),
            ))
            ->addGroupBy('p_level')
        ;
    }

    private function processDimensions(): void
    {
        $i = 0;

        foreach ($this->metadata->getDimensionMetadatas() as $levelProperty => $metadata) {
            $isEntity = $this->queryBuilder
                ->getEntityManager()
                ->getClassMetadata($this->metadata->getSummaryClass())
                ->hasAssociation($levelProperty);

            $hierarchyMetadata = $metadata->getHierarchy();

            // hierarchical dimension

            if ($hierarchyMetadata !== null) {
                $dimensionProperty = $metadata->getSummaryProperty();

                // add select for each of the properties

                foreach ($hierarchyMetadata->getProperties() as $property) {
                    $name = $property->getName();
                    $alias = \sprintf('d%d_', $i++);

                    $this->queryBuilder
                        ->addSelect(\sprintf(
                            'root.%s.%s AS %s',
                            $dimensionProperty,
                            $name,
                            $alias,
                        ))
                        ->addGroupBy($alias)
                    ;
                }
            } elseif ($isEntity) {
                $alias = \sprintf('d%d_', $i++);

                $this->queryBuilder
                    ->addSelect(\sprintf(
                        'IDENTITY(root.%s) AS %s',
                        $levelProperty,
                        $alias,
                    ))
                    ->addGroupBy($alias)
                ;
            } else {
                $alias = \sprintf('d%d_', $i++);

                $this->queryBuilder
                    ->addSelect(\sprintf(
                        'root.%s AS %s',
                        $levelProperty,
                        $alias,
                    ))
                    ->addGroupBy($alias)
                ;
            }
        }
    }

    private function processMeasures(): void
    {
        foreach ($this->metadata->getMeasureMetadatas() as $field => $metadata) {
            $function = $metadata->getFunction();
            $function = reset($function);

            if (!$function instanceof AggregateFunction) {
                throw new UnexpectedValueException(\sprintf(
                    'Function must be an instance of AggregateFunction, got "%s".',
                    get_debug_type($function),
                ));
            }

            $function = $function->getSummaryToSummaryDQLFunction();

            $function = \sprintf(
                $function,
                \sprintf('root.%s', $field),
            );

            $this->queryBuilder->addSelect($function);
        }
    }

    private function processConstraints(): void
    {
        $partitionMetadata = $this->metadata->getPartition();
        $partitionProperty = $partitionMetadata->getSummaryProperty();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

        $lowerBound = $this->start->getLowerBound();
        $upperBound = $this->end->getUpperBound();

        $lowerLevel = PartitionUtil::getLowerLevel($this->start);

        if ($lowerLevel === null) {
            throw new LogicException('The lowest level must be rolled up from the source');
        }

        $this->queryBuilder
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
        ;
    }

    private function processGroupings(): void
    {
        $groupingsProperty = $this->metadata->getGroupingsProperty();

        $this->queryBuilder
            ->addSelect(\sprintf(
                "root.%s",
                $groupingsProperty,
            ))
            ->addGroupBy(\sprintf(
                "root.%s",
                $groupingsProperty,
            ))
        ;
    }

    /**
     * @return iterable<string>
     */
    private function createSQL(): iterable
    {
        $query = $this->queryBuilder->getQuery();
        $result = $query->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }
}
