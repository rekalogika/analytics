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

use Doctrine\DBAL\Connection;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Handler\DirtyFlagsHandler;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\Handler\SummaryHandler;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresher;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The times of the dirty flags are absolute times, stored in columns with
 * time zone.
 */
final class DirtyFlagTimeTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnection()->executeStatement('DELETE FROM rekalogika_summary_dirty');
    }

    /**
     * The time zone of PHP and of the database session do not affect the
     * stored time.
     */
    public function testTimeZoneIndependence(): void
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');

        try {
            $this->getConnection()->executeStatement("SET LOCAL TIME ZONE 'America/New_York'");

            $created = new \DateTimeImmutable('2026-01-02 03:04:05');
            $dirtyFlags = $this->getDirtyFlagsHandler();
            $dirtyFlags->insertDirtyFlag(new DirtyFlag(
                class: OrderSummary::class,
                level: null,
                key: null,
                created: $created,
            ));

            $epoch = $this->getConnection()->fetchOne('SELECT EXTRACT(EPOCH FROM created)::bigint FROM rekalogika_summary_dirty');
            $this->assertTrue(\is_int($epoch) || \is_string($epoch));
            $this->assertSame($created->getTimestamp(), (int) $epoch);

            $range = $dirtyFlags->getNewFlagsTimeRange();
            $this->assertNotNull($range);
            $this->assertSame($created->getTimestamp(), $range[0]->getTimestamp());
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    /**
     * The flag of the upper partition created by the refresh of a partition
     * inherits the time of the earliest flag of the refreshed partition, the
     * earliest change not yet reflected in the upper partition.
     */
    public function testUpperPartitionFlagInheritsTheTime(): void
    {
        $partition = $this->getSummaryHandler()
            ->getPartition()
            ->createLowestPartitionFromSourceValue(1);

        $upperPartition = $partition->getContaining();
        $this->assertNotNull($upperPartition);

        $earliest = new \DateTimeImmutable('2026-01-02 03:04:05', new \DateTimeZone('UTC'));
        $dirtyFlags = $this->getDirtyFlagsHandler();

        $dirtyFlags->insertDirtyFlags([
            $dirtyFlags->createDirtyFlagForPartition($partition, $earliest->modify('+1 hour')),
            $dirtyFlags->createDirtyFlagForPartition($partition, $earliest),
        ]);

        $this->getSummaryRefresher()->refreshPartition($partition);

        $this->assertSame(
            [$earliest->getTimestamp()],
            $this->getFlagTimes($upperPartition->getLevel(), $upperPartition->getKey()),
        );
    }

    /**
     * Without a flag to inherit from, e.g. in a manual refresh, the flag of
     * the upper partition has the time of the refresh.
     */
    public function testUpperPartitionFlagWithoutFlagToInheritFrom(): void
    {
        $partition = $this->getSummaryHandler()
            ->getPartition()
            ->createLowestPartitionFromSourceValue(1);

        $upperPartition = $partition->getContaining();
        $this->assertNotNull($upperPartition);

        $this->getSummaryRefresher()->refreshPartition($partition);

        $times = $this->getFlagTimes($upperPartition->getLevel(), $upperPartition->getKey());
        $this->assertCount(1, $times);
        $this->assertLessThan(60, abs($times[0] - time()));
    }

    /**
     * @return list<int>
     */
    private function getFlagTimes(int $level, int $key): array
    {
        $result = $this->getConnection()->fetchFirstColumn(
            'SELECT EXTRACT(EPOCH FROM created)::bigint FROM rekalogika_summary_dirty WHERE class = ? AND level = ? AND key = ? ORDER BY created',
            [OrderSummary::class, $level, $key],
        );

        $times = [];

        foreach ($result as $time) {
            $this->assertTrue(\is_int($time) || \is_string($time));
            $times[] = (int) $time;
        }

        return $times;
    }

    private function getSummaryRefresher(): SummaryRefresher
    {
        $factory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $factory);

        return $factory->createSummaryRefresher(OrderSummary::class);
    }

    private function getSummaryHandler(): SummaryHandler
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory->getSummary(OrderSummary::class);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getDirtyFlagsHandler(): DirtyFlagsHandler
    {
        return $this->getSummaryHandler()->getDirtyFlags();
    }
}
