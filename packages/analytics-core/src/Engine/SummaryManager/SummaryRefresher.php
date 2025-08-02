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

namespace Rekalogika\Analytics\Engine\SummaryManager;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\RefreshAgent\DefaultRefreshAgentStrategy;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStrategy;
use Rekalogika\Analytics\Engine\SummaryManager\Event\DeleteRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RollUpRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\SummaryManager\Handler\SummaryHandler;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;

/**
 * @internal
 */
final class SummaryRefresher
{
    private readonly SummaryHandler $summaryHandler;

    private ?SqlFactory $sqlFactory = null;

    public function __construct(
        HandlerFactory $handlerFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->summaryHandler = $handlerFactory->getSummary(
            summaryClass: $this->metadata->getSummaryClass(),
        );
    }

    private function getConnection(): Connection
    {
        return $this->entityManager->getConnection();
    }

    public function getSqlFactory(): SqlFactory
    {
        return $this->sqlFactory ??= new SqlFactory(
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
            partitionManager: $this->summaryHandler->getPartition(),
        );
    }

    /**
     * @param iterable<DecomposedQuery> $queries
     */
    private function executeQueries(iterable $queries): void
    {
        foreach ($queries as $query) {
            $query->execute($this->getConnection());
        }
    }

    public function refresh(
        ?RefreshAgentStrategy $refreshAgentStrategy = null,
        ?int $maxIterations = null,
    ): void {
        if ($refreshAgentStrategy === null) {
            $refreshAgentStrategy = new DefaultRefreshAgentStrategy();
        }

        $i = 0;

        do {
            $i++;

            $this->convertNewRecordsToDirtyFlags();
            $count = $this->refreshOne($refreshAgentStrategy);

            if ($count === 0) {
                break;
            }

            if ($maxIterations !== null && $i >= $maxIterations) {
                break;
            }
        } while (true);
    }

    private function refreshOne(RefreshAgentStrategy $refreshAgentStrategy): int
    {
        $dirtyPartitions = $this->summaryHandler
            ->getDirtyFlags()
            ->getDirtyPartitions($refreshAgentStrategy);

        foreach ($dirtyPartitions as $partition) {
            $this->refreshPartition($partition->getPartition());
        }

        return \count($dirtyPartitions);
    }

    /**
     * Refreshes the specified range. If start and end is not specified, it
     * does the refresh for new entities, not previously processed.
     */
    public function manualRefresh(
        int|string|null $start,
        int|string|null $end,
        int $batchSize,
        ?string $resumeId,
    ): void {
        $inputStart = $start;
        $inputEnd = $end;

        $sourceLatestKey = $this->summaryHandler->getSource()->getLatestKey();
        $summaryLatestKey = $this->summaryHandler->getLatestKey();

        // determine start
        // first check the stored latest key, and start from there

        if ($start === null) {
            $start = $summaryLatestKey;

            if (\is_int($start)) {
                $start++;
            }
        }

        // if still null, then start from the earliest id of the source entity

        if ($start === null) {
            $start = $this->summaryHandler
                ->getSource()
                ->getEarliestKey();
        }

        // if still null, then return

        if ($start === null) {
            return;
        }

        // if end is not provided, then use the latest value

        if ($end === null) {
            $end = $sourceLatestKey;
        }

        // if start is greater than end, then return

        if ($start > $end) {
            $end = $start;
        }

        $startEvent = new RefreshStartEvent(
            class: $this->metadata->getSummaryClass(),
            inputStartValue: $inputStart,
            inputEndValue: $inputEnd,
            actualStartValue: $start,
            actualEndValue: $end,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        // if end is same as start, and not yet recorded in the summary,

        if ($start === $end && $summaryLatestKey !== null) {
            $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
            return;
        }

        // update ranges

        $doWork = false;

        if ($resumeId === null) {
            $doWork = true;
        }

        foreach ($this->getRangesForManualRefresh($start, $end) as $range) {
            foreach ($range->batch($batchSize) as $batchRange) {
                if ($batchRange->getSignature() === $resumeId) {
                    $doWork = true;
                }

                if ($doWork) {
                    $this->refreshRange($batchRange);
                }
            }
        }

        // update latest key

        if ($end > $summaryLatestKey) {
            $this->summaryHandler->updateLatestKey($end);
        }

        // remove new entity flags

        $this->getConnection()->beginTransaction();
        $this->summaryHandler->getDirtyFlags()->removeNewFlags();

        $sourceLatestKey = $this->summaryHandler
            ->getSource()
            ->getLatestKey();

        if ($end === null || $end >= $sourceLatestKey) {
            $this->getConnection()->commit();
        } else {
            $this->getConnection()->rollBack();
        }

        // dispatch end event

        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }


    /**
     * @return array<DirtyFlag>
     */
    public function refreshPartition(Partition $partition): array
    {
        $range = new PartitionRange($partition, $partition);

        return $this->refreshRange($range);
    }

    /**
     * @return iterable<PartitionRange>
     */
    private function getRangesForManualRefresh(
        mixed $start,
        mixed $end,
    ): iterable {
        $end = $this->summaryHandler
            ->getPartition()
            ->createLowestPartitionFromSourceValue($end);

        $start = $this->summaryHandler
            ->getPartition()
            ->createLowestPartitionFromSourceValue($start);

        if (PartitionUtil::isGreaterThan($start, $end)) {
            return;
        }

        // lowest level range

        yield $range = new PartitionRange($start, $end);

        while (true) {
            // determine the end of the upper level

            $end = $range->getEnd();
            $upperLevelEnd = $end->getContaining();

            // if the upper level end is null then break

            if ($upperLevelEnd === null) {
                break;
            }

            // if the upper level upper bound is the same as the current level's,
            // then use the upper level as the end range, else use the previous
            // partition

            if ($upperLevelEnd->getUpperBound() === $end->getUpperBound()) {
                $end = $upperLevelEnd;
            } else {
                $end = $upperLevelEnd->getPrevious();
            }

            // if end is null then break, should not happen, but just in case

            if ($end === null) {
                break;
            }

            // determine the start of the upper level

            $start = $range->getStart();
            $upperLevelStart = $start->getContaining();

            // if the upper level start is null then break, should not happen,
            // but just in case

            if ($upperLevelStart === null) {
                break;
            }

            // create the new range

            yield $range = new PartitionRange($upperLevelStart, $end);
        }
    }

    /**
     * @return array<DirtyFlag>
     */
    private function refreshRange(PartitionRange $range): array
    {
        $startEvent = new RefreshRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        // get the latest key of the summary at the beginning
        $summaryLatestKey = $this->summaryHandler->getLatestKey();

        $this->getConnection()->beginTransaction();

        $this->deleteSummaryRange($range);
        $this->summaryHandler->getDirtyFlags()->removeDirtyFlags($range);

        if (PartitionUtil::isLowestLevel($range)) {
            $this->rollUpSourceToSummary($range);
        } else {
            $this->rollUpSummaryToSummary($range);
        }

        $dirtyFlags = [];

        foreach ($range as $partition) {
            $necessaryToMarkUpperAsDirty =
                $this->isNecessaryToMarkUpperPartitionAsDirty(
                    partition: $partition,
                    summaryLatestKey: $summaryLatestKey,
                );

            if ($necessaryToMarkUpperAsDirty) {
                $upperPartition = $partition->getContaining();

                if ($upperPartition === null) {
                    continue;
                }

                $dirtyFlag = $this->summaryHandler
                    ->getDirtyFlags()
                    ->createDirtyFlagForPartition($upperPartition);

                $this->entityManager->persist($dirtyFlag);

                $dirtyFlags[] = $dirtyFlag;
            }
        }

        $this->entityManager->flush();
        $this->getConnection()->commit();
        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());

        return $dirtyFlags;
    }

    private function isNecessaryToMarkUpperPartitionAsDirty(
        Partition $partition,
        int|string|null $summaryLatestKey,
    ): bool {
        $isNew = $summaryLatestKey === null
            || $partition->getUpperBound() > $summaryLatestKey;

        $upperPartition = $partition->getContaining();

        if ($upperPartition === null) {
            return false;
        }

        // special case for new partitions: only mark the upper partition as
        // dirty if the current partition is at the end of the upper partition

        if ($isNew) {
            return $upperPartition->getUpperBound() === $partition->getUpperBound();
        }

        // if upper partition's upper bound is greater than latest key in
        // summary, then it is not necessary to mark the upper partition as
        // dirty

        return $upperPartition->getUpperBound() <= $summaryLatestKey;
    }

    private function deleteSummaryRange(PartitionRange $range): void
    {
        $startEvent = new DeleteRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        $queries = $this->getSqlFactory()
            ->getDeleteExistingSummaryQuery()
            ->withBoundary($range->getStart(), $range->getEnd())
            ->getQueries();

        $this->executeQueries($queries);
        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }

    private function rollUpSourceToSummary(PartitionRange $range): void
    {
        $startEvent = new RollUpRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        $queries = $this->getSqlFactory()
            ->getRollUpSourceToSummaryQuery()
            ->withBoundary(
                start: $range->getStart(),
                end: $range->getEnd(),
            )
            ->getQueries();

        $this->executeQueries($queries);
        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }

    private function rollUpSummaryToSummary(PartitionRange $range): void
    {
        $startEvent = new RollUpRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        $queries = $this->getSqlFactory()
            ->getRollUpSummaryToSummaryQuery()
            ->withBoundary(
                start: $range->getStart(),
                end: $range->getEnd(),
            )
            ->getQueries();

        $this->executeQueries($queries);
        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }



    /**
     * @return array<DirtyFlag>
     */
    public function convertNewRecordsToDirtyFlags(): array
    {
        $this->getConnection()->beginTransaction();

        $range = $this->summaryHandler->getNewEntitiesRange();

        if ($range === null) {
            $this->getConnection()->rollBack();
            return [];
        }

        $this->summaryHandler->getDirtyFlags()->removeNewFlags();

        $dirtyFlags = [];

        foreach ($range as $partition) {
            $dirtyFlag = $this->summaryHandler
                ->getDirtyFlags()
                ->createDirtyFlagForPartition($partition);

            $dirtyFlags[] = $dirtyFlag;
            $this->entityManager->persist($dirtyFlag);
        }

        // update the latest key of the summary handler

        $sourceLatestKey = $this->summaryHandler->getSource()->getLatestKey();

        if ($sourceLatestKey !== null) {
            $this->summaryHandler->updateLatestKey($sourceLatestKey);
        }

        // flush the changes

        $this->entityManager->flush();
        $this->getConnection()->commit();

        return $dirtyFlags;
    }
}
