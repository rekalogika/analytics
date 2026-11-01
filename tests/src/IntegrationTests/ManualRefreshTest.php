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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresher;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\TestIntegerPartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ManualRefreshTest extends KernelTestCase
{
    private function createRefresher(): SummaryRefresher
    {
        $factory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $factory);

        return $factory->createSummaryRefresher(OrderSummary::class);
    }

    /**
     * Gets the number of records summarized in the partitions of the given
     * level whose keys fall within the given bounds.
     */
    private function getSummarizedCount(int $level, int $lowerBound, int $upperBound): int
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // the grouping set with all the dimensions rolled up, it has a single
        // row per partition

        $groupings = $entityManager->createQueryBuilder()
            ->select('MAX(s.__groupings)')
            ->from(OrderSummary::class, 's')
            ->getQuery()
            ->getSingleScalarResult();

        $count = $entityManager->createQueryBuilder()
            ->select('SUM(s.count)')
            ->from(OrderSummary::class, 's')
            ->where('s.partition.level = :level')
            ->andWhere('s.partition.key >= :lowerBound')
            ->andWhere('s.partition.key < :upperBound')
            ->andWhere('s.__groupings = :groupings')
            ->setParameter('level', $level)
            ->setParameter('lowerBound', $lowerBound)
            ->setParameter('upperBound', $upperBound)
            ->setParameter('groupings', $groupings)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    private function assertPartitionMatchesLowestLevel(TestIntegerPartition $partition): void
    {
        $lowerBound = $partition->getLowerBound();
        $upperBound = $partition->getUpperBound();

        $this->assertSame(
            $this->getSummarizedCount(2, $lowerBound, $upperBound),
            $this->getSummarizedCount($partition->getLevel(), $lowerBound, $upperBound),
            \sprintf('Count of partition %s', (string) $partition),
        );
    }

    /**
     * With a batch size larger than one, each upper level partition must
     * still be rolled up into its own rows, not merged with the other
     * partitions in the same batch.
     */
    public function testBatchedRefreshKeepsUpperPartitionsSeparate(): void
    {
        // the lowest partitions of TestIntegerPartition contain 4 records,
        // and the upper partitions contain 4 partitions of the level below.
        // refreshing [1, 31] in batches of 4 processes 8 partitions at level
        // 2, and 2 partitions at level 4: [0, 16) and [16, 32)

        $refresher = $this->createRefresher();
        $refresher->manualRefresh(start: 1, end: 31, batchSize: 4, resumeId: null);

        $first = TestIntegerPartition::createFromSourceValue(0, 4);
        $second = TestIntegerPartition::createFromSourceValue(16, 4);

        $this->assertGreaterThan(0, $this->getSummarizedCount(2, 0, 16));
        $this->assertGreaterThan(0, $this->getSummarizedCount(2, 16, 32));
        $this->assertPartitionMatchesLowestLevel($first);
        $this->assertPartitionMatchesLowestLevel($second);

        // refreshing the partition that is not the first in its batch must
        // replace its rows, not add to them

        $refresher->refreshPartition($second);

        $this->assertPartitionMatchesLowestLevel($first);
        $this->assertPartitionMatchesLowestLevel($second);
    }
}
