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

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\Handler\SummaryHandler;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ValidAsOfTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnection()->executeStatement('DELETE FROM rekalogika_summary_dirty');
    }

    /**
     * Without pending changes, the result is valid as of the time of the
     * query.
     */
    public function testUpToDate(): void
    {
        $before = time();
        $validAsOf = $this->getResult()->getValidAsOf();
        $after = time();

        $this->assertNotNull($validAsOf);
        $this->assertGreaterThanOrEqual($before, $validAsOf->getTimestamp());
        $this->assertLessThanOrEqual($after, $validAsOf->getTimestamp());
        $this->assertSame('UTC', $validAsOf->getTimezone()->getName());
    }

    /**
     * A pending change makes the result valid as of the time of the change.
     * Every cell of the cube has the same time, including the cells of a
     * diced cube.
     */
    public function testPendingDirtyFlag(): void
    {
        $created = new \DateTimeImmutable('2026-01-02 03:04:05', new \DateTimeZone('UTC'));
        $dirtyFlags = $this->getSummaryHandler()->getDirtyFlags();
        $partition = $this->getSummaryHandler()
            ->getPartition()
            ->createLowestPartitionFromSourceValue(1);

        $dirtyFlags->insertDirtyFlags([
            $dirtyFlags->createDirtyFlagForPartition($partition, $created->modify('+1 day')),
            $dirtyFlags->createDirtyFlagForPartition($partition, $created),
        ]);

        $result = $this->getResult();
        $this->assertSame($created->getTimestamp(), $result->getValidAsOf()?->getTimestamp());

        $child = $result->drillDown('customerCountry')->first();
        $this->assertNotNull($child);
        $this->assertSame($created->getTimestamp(), $child->getValidAsOf()?->getTimestamp());

        $diced = $child->dice(Criteria::expr()->in('time.civil.year', [2023, 2024]));
        $this->assertSame($created->getTimestamp(), $diced->getValidAsOf()?->getTimestamp());
    }

    /**
     * New records without dirty flags, e.g. inserted with native SQL without
     * the triggers, make the result valid as of the last time the refresh
     * checked the source for new records.
     */
    public function testNewRecordsWithoutFlags(): void
    {
        $sourceChecked = new \DateTimeImmutable('2026-01-02 03:04:05', new \DateTimeZone('UTC'));
        $this->getSummaryHandler()->updateSourceChecked($sourceChecked);
        $this->insertOrder();

        $this->assertSame(
            $sourceChecked->getTimestamp(),
            $this->getResult()->getValidAsOf()?->getTimestamp(),
        );
    }

    /**
     * New records, and the source has never been checked for new records:
     * the time cannot be determined.
     */
    public function testNewRecordsWithoutSourceCheck(): void
    {
        $this->getConnection()->executeStatement('UPDATE rekalogika_summary_properties SET source_checked = NULL');
        $this->insertOrder();

        $this->assertNull($this->getResult()->getValidAsOf());
    }

    /**
     * The time of the source check does not matter without new records.
     */
    public function testSourceCheckWithoutNewRecords(): void
    {
        $this->getConnection()->executeStatement('UPDATE rekalogika_summary_properties SET source_checked = NULL');

        $validAsOf = $this->getResult()->getValidAsOf();

        $this->assertNotNull($validAsOf);
        $this->assertLessThan(60, abs($validAsOf->getTimestamp() - time()));
    }

    private function getResult(): CubeCell
    {
        $summaryManager = static::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        return $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->getResult();
    }

    private function insertOrder(): void
    {
        $connection = $this->getConnection();

        /** @var array<string,mixed> */
        $row = $connection->fetchAssociative('SELECT * FROM "order" ORDER BY id DESC LIMIT 1');
        $this->assertTrue(\is_int($row['id']) || \is_string($row['id']));

        $row['id'] = (int) $row['id'] + 1;
        $connection->insert('"order"', $row);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getSummaryHandler(): SummaryHandler
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory->getSummary(OrderSummary::class);
    }
}
