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

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getDirtyFlagsHandler(): DirtyFlagsHandler
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory->getSummary(OrderSummary::class)->getDirtyFlags();
    }
}
