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
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Roll up lower level summary to higher level by grouping by the entire row set
 */
final class RollUpSummaryToSummaryGroupAllStrategyQuery extends AbstractQuery
{
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

        $partitionKeyProperty = $this->getSimpleQueryBuilder()
            ->resolve($partitionMetadata->getFullyQualifiedPartitionKeyProperty());

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf(
                'MIN(%s) AS p_key',
                $partitionKeyProperty,
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

        $classMetadata = $this->getSimpleQueryBuilder()
            ->getEntityManager()
            ->getClassMetadata($this->metadata->getSummaryClass());

        foreach ($this->metadata->getLeafDimensions() as $name => $dimensionMetadata) {
            $isEntity = $classMetadata->hasAssociation($name);

            if ($isEntity) {
                $alias = \sprintf('d%d_', $i++);

                $this->getSimpleQueryBuilder()
                    ->addSelect(\sprintf(
                        'IDENTITY(root.%s) AS %s',
                        $name,
                        $alias,
                    ))
                    ->addGroupBy($alias)
                ;
            } else {
                $alias = \sprintf('d%d_', $i++);

                $this->getSimpleQueryBuilder()
                    ->addSelect(\sprintf(
                        'root.%s AS %s',
                        $name,
                        $alias,
                    ))
                    ->addGroupBy($alias)
                ;
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

            if (!$function instanceof SummarizableAggregateFunction) {
                continue;
            }

            $sqlField = $this->getSimpleQueryBuilder()->resolve($field);
            $function = $function->getAggregateToAggregateExpression($sqlField);

            $this->getSimpleQueryBuilder()->addSelect($function);
        }
    }

    private function processConstraints(): void
    {
        $partitionMetadata = $this->metadata->getPartition();
        $partitionProperty = $partitionMetadata->getName();
        $partitionKeyProperty = $partitionMetadata->getPartitionKeyProperty();
        $partitionLevelProperty = $partitionMetadata->getPartitionLevelProperty();

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
        ;
    }

    private function processGroupings(): void
    {
        $groupingsProperty = $this->metadata->getGroupingsProperty();

        $this->getSimpleQueryBuilder()
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
        $query = $this->getSimpleQueryBuilder()->getQuery();
        $result = $query->getSQL();

        if (\is_array($result)) {
            yield from $result;
        } else {
            yield $result;
        }
    }
}
