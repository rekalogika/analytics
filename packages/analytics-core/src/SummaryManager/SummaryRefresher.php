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

namespace Rekalogika\Analytics\SummaryManager;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Rekalogika\Analytics\Metadata\SummaryMetadata;
use Rekalogika\Analytics\Model\Entity\SummarySignal;
use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\SummaryManager\Event\DeleteRangeStartEvent;
use Rekalogika\Analytics\SummaryManager\Event\RefreshRangeStartEvent;
use Rekalogika\Analytics\SummaryManager\Event\RefreshStartEvent;
use Rekalogika\Analytics\SummaryManager\Event\RollUpRangeStartEvent;
use Rekalogika\Analytics\SummaryManager\PartitionManager\PartitionManager;
use Rekalogika\Analytics\SummaryManager\Query\SourceIdRangeDeterminer;
use Rekalogika\Analytics\SummaryManager\Query\SummaryPropertiesManager;
use Rekalogika\Analytics\Util\PartitionUtil;

final readonly class SummaryRefresher
{
    private SqlFactory $sqlFactory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $metadata,
        private PartitionManager $partitionManager,
        private SummarySignalManager $signalManager,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->sqlFactory = new SqlFactory(
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
            partitionManager: $this->partitionManager,
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
     * @param iterable<string> $queries
     */
    private function executeQueries(iterable $queries): void
    {
        foreach ($queries as $query) {
            $this->getConnection()->executeStatement($query);
        }
    }

    /**
     * Refreshes the specified range. If start and end is not specified, it
     * does the refresh for new entities, not previously processed.
     */
    public function refresh(
        int|string|null $start,
        int|string|null $end,
        int $batchSize,
        ?string $resumeId,
    ): void {
        $inputStart = $start;
        $inputEnd = $end;
        $maxOfSource = $this->getMaxId();

        $maxOfSummary = $this->getSummaryPropertiesManager()
            ->getMax($this->metadata->getSummaryClass());

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
            $start = $this->getMinId();
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

        // if end is start, return

        if ($start === $end) {
            $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
            return;
        }

        // update ranges

        $doWork = false;

        if ($resumeId === null) {
            $doWork = true;
        }

        foreach ($this->getRangesForUpdate($start, $end) as $range) {
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

        // remove new entity signals

        $this->removeNewEntitySignals($end);

        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
    }

    public function refreshPartition(Partition $partition): void
    {
        $range = new PartitionRange($partition, $partition);

        $this->refreshRange($range);
    }

    public function refreshNew(): void
    {
        $this->getConnection()->beginTransaction();

        $this->removeNewSignals();
        $maxOfSource = $this->getMaxId();

        $maxOfSummary = $this->getSummaryPropertiesManager()
            ->getMax($this->metadata->getSummaryClass());

        $start = $this->partitionManager
            ->createLowestPartitionFromSourceValue($maxOfSummary);

        $end = $this->partitionManager
            ->createLowestPartitionFromSourceValue($maxOfSource);

        $range = new PartitionRange($start, $end);
        $this->refreshRange($range);

        $this->getSummaryPropertiesManager()->updateMax(
            summaryClass: $this->metadata->getSummaryClass(),
            max: $maxOfSource,
        );

        // special case for refresh new: only mark the upper partition as dirty
        // if the current partition is at the end of the upper partition

        foreach ($range as $partition) {
            $upperLevel = $partition->getContaining();

            if (
                $upperLevel !== null
                && $upperLevel->getUpperBound() === $partition->getUpperBound()
            ) {
                $signal = $this->signalManager->createDirtyPartitionSignal(
                    class: $this->metadata->getSummaryClass(),
                    partition: $upperLevel
                );

                $this->entityManager->persist($signal);
            }
        }

        $this->entityManager->flush();

        $this->getConnection()->commit();
    }

    /**
     * @return iterable<PartitionRange>
     */
    private function getRangesForUpdate(
        mixed $start,
        mixed $end,
    ): iterable {
        $end = $this->partitionManager
            ->createLowestPartitionFromSourceValue($end);

        $start = $this->partitionManager
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

    private function refreshRange(PartitionRange $range): void
    {
        $startEvent = new RefreshRangeStartEvent(
            class: $this->metadata->getSummaryClass(),
            range: $range,
        );

        $this->eventDispatcher?->dispatch($startEvent);

        $this->getConnection()->beginTransaction();
        $this->deleteSummaryRange($range);
        $this->removeDirtyPartitionSignals($range);

        if (PartitionUtil::isLowestLevel($range)) {
            $this->rollUpSourceToSummary($range);
        } else {
            $this->rollUpSummaryToSummary($range);
        }

        $this->getConnection()->commit();

        $this->eventDispatcher?->dispatch($startEvent->createEndEvent());
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

    private function removeNewEntitySignals(int|string|null $end): void
    {
        $this->getConnection()->beginTransaction();

        $this->entityManager->createQueryBuilder()
            ->delete(SummarySignal::class, 's')
            ->where('s.class = :class')
            ->andWhere('s.level IS NULL')
            ->andWhere('s.key IS NULL')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->execute();

        $maxOfSource = $this->getMaxId();

        if ($end === null || $end >= $maxOfSource) {
            $this->getConnection()->commit();
        } else {
            $this->getConnection()->rollBack();
        }
    }

    private function removeDirtyPartitionSignals(PartitionRange $range): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(SummarySignal::class, 's')
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

    private function removeNewSignals(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(SummarySignal::class, 's')
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

    /**
     * @param class-string $class
     */
    private function createRangeDeterminer(string $class): SourceIdRangeDeterminer
    {
        return new SourceIdRangeDeterminer(
            class: $class,
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
        );
    }

    /**
     * @param class-string $class
     */
    private function getMaxIdOfClass(string $class): int|string|null
    {
        return $this->createRangeDeterminer($class)->getMaxId();
    }

    /**
     * @param class-string $class
     */
    private function getMinIdOfClass(string $class): int|string|null
    {
        return $this->createRangeDeterminer($class)->getMinId();
    }

    private function getMaxId(): int|string|null
    {
        $classes = $this->metadata->getSourceClasses();
        $max = null;

        foreach ($classes as $class) {
            if ($max === null) {
                $max = $this->getMaxIdOfClass($class);
                continue;
            }

            $max = max($max, $this->getMaxIdOfClass($class));
        }

        return $max;
    }

    private function getMinId(): int|string|null
    {
        $classes = $this->metadata->getSourceClasses();
        $min = null;

        foreach ($classes as $class) {
            if ($min === null) {
                $min = $this->getMinIdOfClass($class);
                continue;
            }

            $min = min($min, $this->getMinIdOfClass($class));
        }

        return $min;
    }
}
