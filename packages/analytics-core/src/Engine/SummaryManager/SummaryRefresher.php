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
use Rekalogika\Analytics\Engine\SummaryManager\Component\ComponentFactory;
use Rekalogika\Analytics\Engine\SummaryManager\Component\SummaryComponent;
use Rekalogika\Analytics\Engine\SummaryManager\DirtyFlag\DirtyFlagGenerator;
use Rekalogika\Analytics\Engine\SummaryManager\Event\DeleteRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RefreshStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Event\RollUpRangeStartEvent;
use Rekalogika\Analytics\Engine\SummaryManager\Query\SummaryPropertiesManager;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;

final class SummaryRefresher
{
    private readonly SummaryComponent $summaryComponent;

    private readonly SqlFactory $sqlFactory;

    private int|string|null $minIdOfSource = null;

    private int|string|null $maxIdOfSource = null;

    private int|string|null $maxIdOfSummary = null;


    public function __construct(
        ComponentFactory $componentFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly SummaryMetadata $metadata,
        private readonly DirtyFlagGenerator $dirtyFlagGenerator,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->summaryComponent = $componentFactory->getSummary(
            summaryClass: $this->metadata->getSummaryClass(),
        );

        $this->sqlFactory = new SqlFactory(
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
            partitionManager: $this->summaryComponent->getPartition(),
        );
    }

    private function getConnection(): Connection
    {
        return $this->entityManager->getConnection();
    }

    private function getSummaryPropertiesManager(): SummaryPropertiesManager
    {
        return new SummaryPropertiesManager($this->entityManager);
    }

    /**
     * @param iterable<string|DecomposedQuery> $queries
     */
    private function executeQueries(iterable $queries): void
    {
        foreach ($queries as $query) {
            if (\is_string($query)) {
                $this->getConnection()->executeStatement($query);
            } else {
                $query->execute($this->getConnection());
            }
        }
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

        $maxOfSource = $this->getMaxIdOfSource();
        $maxOfSummary = $this->getMaxIdOfSummary();

        // determine start
        // first check the stored max value, and start from there

        if ($start === null) {
            $start = $maxOfSummary;
            $startIsExclusive = true; // not used yet

            // @todo refactor, should use the $startIsExclusive flag above
            if (\is_int($start)) {
                $start++;
            }
        }

        // if still null, then start from the lowest id of the source entity

        if ($start === null) {
            $start = $this->getMinIdOfSource();
            $startIsExclusive = false; // not used yet
        }

        // if still null, then return

        if ($start === null) {
            return;
        }

        // if end is not provided, then use the max value

        if ($end === null) {
            $end = $maxOfSource;
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

        if ($start === $end && $maxOfSummary !== null) {
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

        // update the max

        if ($end > $maxOfSummary) {
            $this->getSummaryPropertiesManager()->updateMax(
                summaryClass: $this->metadata->getSummaryClass(),
                max: $end,
            );
        }

        // remove new entity flags

        $this->getConnection()->beginTransaction();
        $this->removeNewFlags();
        $maxOfSource = $this->getMaxIdOfSource();

        if ($end === null || $end >= $maxOfSource) {
            $this->getConnection()->commit();
        } else {
            $this->getConnection()->rollBack();
        }

        // dispatch end event

        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }


    /**
     * @return iterable<DirtyFlag>
     */
    public function refreshPartition(Partition $partition): iterable
    {
        $range = new PartitionRange($partition, $partition);

        return $this->refreshRange($range);
    }

    // public function refreshNew(): void
    // {
    //     $this->getConnection()->beginTransaction();

    //     $this->removeNewFlags();
    //     $maxOfSource = $this->getMaxIdOfSource();

    //     $maxOfSummary = $this->getSummaryPropertiesManager()
    //         ->getMax($this->metadata->getSummaryClass());

    //     $start = $this->partitionManager
    //         ->createLowestPartitionFromSourceValue($maxOfSummary);

    //     $end = $this->partitionManager
    //         ->createLowestPartitionFromSourceValue($maxOfSource);

    //     $range = new PartitionRange($start, $end);
    //     $this->refreshRange($range);

    //     $this->getSummaryPropertiesManager()->updateMax(
    //         summaryClass: $this->metadata->getSummaryClass(),
    //         max: $maxOfSource,
    //     );

    //     // special case for refresh new: only mark the upper partition as dirty
    //     // if the current partition is at the end of the upper partition

    //     foreach ($range as $partition) {
    //         $upperLevel = $partition->getContaining();

    //         if (
    //             $upperLevel !== null
    //             && $upperLevel->getUpperBound() === $partition->getUpperBound()
    //         ) {
    //             $dirtyFlag = $this->dirtyFlagGenerator->createDirtyFlag(
    //                 class: $this->metadata->getSummaryClass(),
    //                 partition: $upperLevel,
    //             );

    //             $this->entityManager->persist($dirtyFlag);
    //         }
    //     }

    //     $this->entityManager->flush();

    //     $this->getConnection()->commit();
    // }

    /**
     * @return iterable<PartitionRange>
     */
    private function getRangesForManualRefresh(
        mixed $start,
        mixed $end,
    ): iterable {
        $end = $this->summaryComponent
            ->getPartition()
            ->createLowestPartitionFromSourceValue($end);

        $start = $this->summaryComponent
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

    //
    // roll up methods
    //

    /**
     * @return iterable<DirtyFlag>
     */
    private function refreshRange(PartitionRange $range): iterable
    {
        $startEvent = new RefreshRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        // get the max of the summary at the beginning
        $maxOfSummary = $this->getMaxIdOfSummary();

        $this->getConnection()->beginTransaction();
        $this->deleteSummaryRange($range);
        $this->removeDirtyFlags($range);

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
                    maxOfSummary: $maxOfSummary,
                );

            if ($necessaryToMarkUpperAsDirty) {
                $upperPartition = $partition->getContaining();

                if ($upperPartition === null) {
                    continue;
                }

                $dirtyFlag = $this->dirtyFlagGenerator->createDirtyFlag(
                    class: $this->metadata->getSummaryClass(),
                    partition: $upperPartition,
                );

                $this->entityManager->persist($dirtyFlag);

                $dirtyFlags[] = $dirtyFlag;
            }
        }

        $this->getConnection()->commit();
        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());

        return $dirtyFlags;
    }

    private function isNecessaryToMarkUpperPartitionAsDirty(
        Partition $partition,
        int|string|null $maxOfSummary,
    ): bool {
        $isNew = $maxOfSummary === null || $partition->getUpperBound() > $maxOfSummary;
        $upperPartition = $partition->getContaining();

        if ($upperPartition === null) {
            return false;
        }

        // special case for new partitions: only mark the upper partition as
        // dirty if the current partition is at the end of the upper partition

        if ($isNew) {
            return $upperPartition->getUpperBound() === $partition->getUpperBound();
        }

        // if upper partition's upper bound is greater than max of summary, then
        // it is not necessary to mark the upper partition as dirty

        return $upperPartition->getUpperBound() <= $maxOfSummary;
    }

    private function deleteSummaryRange(PartitionRange $range): void
    {
        $startEvent = new DeleteRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        $queries = $this->sqlFactory->createDeleteSummaryQuery(
            start: $range->getStart(),
            end: $range->getEnd(),
        );

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

        $queries = $this->sqlFactory
            ->createInsertIntoSelectForRollingUpSourceToSummaryQuery(
                start: $range->getStart(),
                end: $range->getEnd(),
            );

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

        $queries = $this->sqlFactory
            ->createInsertIntoSelectForRollingUpSummaryToSummaryQuery(
                start: $range->getStart(),
                end: $range->getEnd(),
            );

        $this->executeQueries($queries);

        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }

    private function removeDirtyFlags(PartitionRange $range): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(DirtyFlag::class, 's')
            ->where('s.class = :class')
            ->andWhere('s.level = :level')
            ->andWhere('s.key >= :start')
            ->andWhere('s.key < :end')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->setParameter('level', $range->getLevel())
            ->setParameter('start', $range->getLowerBound())
            ->setParameter('end', $range->getUpperBound())
            ->getQuery()
            ->execute();
    }

    private function removeNewFlags(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(DirtyFlag::class, 's')
            ->where('s.class = :class')
            ->andWhere('s.level IS NULL')
            ->andWhere('s.key IS NULL')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->execute();
    }

    //
    // min-max determiner
    //

    private function getMaxIdOfSource(): int|string|null
    {
        return $this->maxIdOfSource ??= $this->summaryComponent
            ->getSource()
            ->getHighestIdentifier();
    }

    private function getMinIdOfSource(): int|string|null
    {
        return $this->minIdOfSource ??= $this->summaryComponent
            ->getSource()
            ->getLowestIdentifier();
    }

    private function getMaxIdOfSummary(): int|string|null
    {
        return $this->maxIdOfSummary ??= $this->getSummaryPropertiesManager()
            ->getMax($this->metadata->getSummaryClass());
    }

    /**
     * @return iterable<DirtyFlag>
     */
    public function convertNewRecordsToDirtyFlags(): iterable
    {
        $this->getConnection()->beginTransaction();

        $range = $this->getNewEntitiesRange();

        if ($range === null) {
            $this->getConnection()->rollBack();
            return;
        }

        $this->removeNewFlags();

        foreach ($range as $partition) {
            yield $dirtyFlag = $this->dirtyFlagGenerator->createDirtyFlag(
                class: $this->metadata->getSummaryClass(),
                partition: $partition,
            );

            $this->entityManager->persist($dirtyFlag);
        }

        $this->entityManager->flush();
        $this->getConnection()->commit();
    }

    private function getNewEntitiesRange(): ?PartitionRange
    {
        $maxOfSummary = $this->getMaxIdOfSummary();
        $maxOfSource = $this->getMaxIdOfSource();

        if ($maxOfSummary === null) {
            $minOfSource = $this->getMinIdOfSource();

            if ($minOfSource === null) {
                return null;
            }

            $start = $this->summaryComponent
                ->getPartition()
                ->createLowestPartitionFromSourceValue($minOfSource);
        } else {
            $start = $this->summaryComponent
                ->getPartition()
                ->createLowestPartitionFromSourceValue($maxOfSummary);
        }

        if ($maxOfSource === null) {
            return null;
        }

        $end = $this->summaryComponent
            ->getPartition()
            ->createLowestPartitionFromSourceValue($maxOfSource);

        if (PartitionUtil::isGreaterThan($start, $end)) {
            return null;
        }

        return new PartitionRange($start, $end);
    }
}
