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
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Handler\Query\SourceIdRangeDeterminer;
use Rekalogika\Analytics\Engine\Handler\Query\SummaryPropertiesManager;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * Determines the time before which every change to the source is reflected
 * in the summary table. See `CubeCell::getValidAsOf()`.
 *
 * Must be executed before the query against the summary table. The reads are
 * ordered so that a refresh running concurrently can only make the time
 * earlier than necessary, never later:
 *
 * 1. The time of the query. A change made before it is visible to the
 *    following reads.
 * 2. The latest summarized key, and the time the source was last checked for
 *    new records, read together.
 * 3. The latest key of the source. If it is after the latest summarized key,
 *    the records after it were inserted after the last check of the source.
 *    They may have no dirty flags.
 * 4. The dirty flags. The records up to the latest summarized key read in 2.
 *    are either flagged, or already reflected in the summary table, which is
 *    read last.
 */
final readonly class ValidAsOfQuery
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SummaryMetadata $metadata,
    ) {}

    /**
     * Returns null if it cannot be determined: there are new records, and
     * the source has never been checked for new records.
     */
    public function getValidAsOf(): ?\DateTimeImmutable
    {
        // truncated to the precision of the stored times

        $validAsOf = new \DateTimeImmutable('@' . time());

        [$summaryLatestKey, $sourceChecked] = (new SummaryPropertiesManager(
            entityManager: $this->entityManager,
            summaryClass: $this->metadata->getSummaryClass(),
        ))->getMaxAndSourceChecked();

        $sourceLatestKey = (new SourceIdRangeDeterminer(
            entityManager: $this->entityManager,
            summaryMetadata: $this->metadata,
        ))->getMaxKey();

        $hasNewRecords = $sourceLatestKey !== null
            && ($summaryLatestKey === null || $sourceLatestKey > $summaryLatestKey);

        if ($hasNewRecords) {
            if ($sourceChecked === null) {
                return null;
            }

            if ($sourceChecked < $validAsOf) {
                $validAsOf = $sourceChecked;
            }
        }

        $earliestDirtyTime = $this->getEarliestDirtyTime();

        if ($earliestDirtyTime !== null && $earliestDirtyTime < $validAsOf) {
            $validAsOf = $earliestDirtyTime;
        }

        return $validAsOf->setTimezone(new \DateTimeZone('UTC'));
    }

    private function getEarliestDirtyTime(): ?\DateTimeImmutable
    {
        /** @var string|null */
        $result = $this->entityManager->createQueryBuilder()
            ->select('MIN(df.created)')
            ->from(DirtyFlag::class, 'df')
            ->where('df.class = :class')
            ->setParameter('class', $this->metadata->getSummaryClass())
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return null;
        }

        return new \DateTimeImmutable($result);
    }
}
