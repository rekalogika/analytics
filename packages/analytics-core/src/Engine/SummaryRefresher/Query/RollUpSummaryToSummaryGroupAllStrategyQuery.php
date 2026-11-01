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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Query;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Core\ValueResolver\IntegerValue;
use Rekalogika\Analytics\Engine\Infrastructure\AbstractQuery;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherQuery;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

/**
 * Roll up lower level summary to higher level by grouping by the entire row set.
 * The rows are classified into the partitions of the given level, so a range
 * may span multiple partitions.
 */
final class RollUpSummaryToSummaryGroupAllStrategyQuery extends AbstractQuery implements SummaryRefresherQuery
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly string $insertSql,
        private readonly int $level,
    ) {
        $simpleQueryBuilder = new SimpleQueryBuilder(
            entityManager: $entityManager,
            from: $this->metadata->getSummaryClass(),
            alias: 'root',
        );

        parent::__construct($simpleQueryBuilder);

        $this->initialize();
        $this->processPartition();
        $this->processDimensions();
        $this->processMeasures();
        $this->processConstraints();
        $this->processGroupings();
    }

    #[\Override]
    public function withBoundary(Partition $start, Partition $end): static
    {
        $clone = clone $this;

        $lowerBound = $start->getLowerBound();
        $upperBound = $end->getUpperBound();
        $lowerLevel = PartitionUtil::getLowerLevel($start);

        if ($start->getLevel() !== $this->level) {
            throw new LogicException(\sprintf(
                'The query is for partition level "%d", but got a range on level "%d"',
                $this->level,
                $start->getLevel(),
            ));
        }

        if ($lowerLevel === null) {
            throw new LogicException('The lowest level must be rolled up from the source');
        }

        $clone->getSimpleQueryBuilder()
            ->setParameter('lowerBound', $lowerBound)
            ->setParameter('upperBound', $upperBound)
            ->setParameter('lowerLevel', $lowerLevel);

        return $clone;
    }

    #[\Override]
    public function getQueries(): iterable
    {
        yield $this->createQuery();
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
        $partitionClass = $partitionMetadata->getPartitionClass();

        // classify the keys of the lower level partitions into the partitions
        // of the current level. the keys are already in the integer domain of
        // the partition, so they are used as is.

        $classifier = $partitionClass::getClassifierExpression(
            input: new IntegerValue($partitionMetadata->getFullyQualifiedPartitionKeyProperty()),
            level: $this->level,
            context: new SourceQueryContext(
                queryBuilder: $this->getSimpleQueryBuilder(),
                summaryMetadata: $this->metadata,
                partitionMetadata: $partitionMetadata,
            ),
        );

        $this->getSimpleQueryBuilder()
            ->addSelect(\sprintf('%s AS p_key', $classifier))
            ->addSelect('0 + :currentLevel AS p_level')
            ->setParameter('currentLevel', $this->level)
            ->addGroupBy('p_key')
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

        $this->getSimpleQueryBuilder()
            ->andWhere(\sprintf(
                'root.%s.%s >= :lowerBound',
                $partitionProperty,
                $partitionKeyProperty,
            ))
            ->andWhere(\sprintf(
                'root.%s.%s < :upperBound',
                $partitionProperty,
                $partitionKeyProperty,
            ))
            ->andWhere(\sprintf(
                'root.%s.%s = :lowerLevel',
                $partitionProperty,
                $partitionLevelProperty,
            ))
        ;

        $this->getSimpleQueryBuilder()
            ->setParameter('lowerBound', '(placeholder) the lower bound')
            ->setParameter('upperBound', '(placeholder) the upper bound')
            ->setParameter('lowerLevel', '(placeholder) the lower level');
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

    private function createQuery(): DecomposedQuery
    {
        $query = $this->getSimpleQueryBuilder()->getQuery();

        return DecomposedQuery::createFromQuery($query)
            ->prependSql($this->insertSql);
    }
}
