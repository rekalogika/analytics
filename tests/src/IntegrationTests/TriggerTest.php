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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentSweeper;
use Rekalogika\Analytics\Engine\Trigger\TriggerInstaller;
use Rekalogika\Analytics\Engine\Trigger\TriggerSqlGenerator;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * The triggers flag the partitions modified outside the ORM. Each test runs
 * in a transaction, the triggers are rolled back with it.
 */
final class TriggerTest extends KernelTestCase
{
    use InteractsWithMessenger;
    use ClockSensitiveTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getTriggerInstaller()->install();
        $this->clearDirtyFlags();
        $this->getConnection()->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');
    }

    /**
     * Native SQL, then the sweeper dispatches the refresh agent.
     */
    public function testNativeUpdateOfSourceRow(): void
    {
        $clock = self::mockTime();

        $this->assertSame(0, $this->getOrderCountIn2030());

        $this->getConnection()->executeStatement(
            'UPDATE "order" SET time = ? WHERE id = ?',
            ['2030-02-01 00:00:00', 50],
        );

        $expected = [$this->getOrderPartitionKey(50)];

        $this->assertSame($expected, $this->getDirtyFlagKeys(OrderSummary::class));
        $this->assertSame($expected, $this->getDirtyFlagKeys(OrderExplicitGroupingSetsSummary::class));
        $this->assertSame([], $this->getDirtyFlagKeys(CustomerSummary::class));

        // the creation time is an absolute time, independent of the time zone
        // of PHP and of the database session

        $created = $this->fetchInt('SELECT MIN(EXTRACT(EPOCH FROM created))::bigint FROM rekalogika_summary_dirty');
        $this->assertLessThan(120, abs($created - time()));

        // the triggers do not dispatch the refresh agent, the sweeper does

        $this->transport('async')->queue()->assertEmpty();

        $sweeper = static::getContainer()->get('rekalogika.analytics.refresh.sweeper');
        $this->assertInstanceOf(RefreshAgentSweeper::class, $sweeper);
        $sweeper->sweep();

        $transport = $this->transport('async');
        $transport->process();

        // past the minimum age of the flags. the run is scheduled from the
        // creation time written by the database, which is not exactly the
        // time of the mock clock, so leave a margin

        $clock->sleep(700);
        $this->getConnection()->executeStatement("UPDATE rekalogika_summary_dirty SET created = created - interval '700 seconds'");
        $this->getConnection()->executeStatement("UPDATE rekalogika_summary_properties SET scheduled_refresh = scheduled_refresh - interval '700 seconds'");

        $transport->process();

        $this->assertSame(1, $this->getOrderCountIn2030());
    }

    public function testNativeUpdateOfUninvolvedColumn(): void
    {
        $this->getConnection()->executeStatement('UPDATE item SET name = name || \'x\'');
        $this->getConnection()->executeStatement('UPDATE customer SET name = name || \'x\'');

        $this->assertSame(0, $this->countDirtyFlags());
    }

    public function testDqlUpdate(): void
    {
        $this->getEntityManager()
            ->createQuery(\sprintf('UPDATE %s o SET o.time = :time WHERE o.id = 50', Order::class))
            ->setParameter('time', new \DateTimeImmutable('2030-02-01'))
            ->execute();

        $this->assertSame([$this->getOrderPartitionKey(50)], $this->getDirtyFlagKeys(OrderSummary::class));
    }

    /**
     * A row deleted, then inserted again with the same key, below the latest
     * summarized key.
     */
    public function testNativeDeleteAndInsert(): void
    {
        $connection = $this->getConnection();

        /** @var array<string,mixed> */
        $row = $connection->fetchAssociative('SELECT * FROM "order" WHERE id = 60');

        $connection->executeStatement('DELETE FROM "order" WHERE id = 60');
        $this->assertSame([$this->getOrderPartitionKey(60)], $this->getDirtyFlagKeys(OrderSummary::class));

        $this->clearDirtyFlags();

        $connection->insert('"order"', $row);
        $this->assertSame([$this->getOrderPartitionKey(60)], $this->getDirtyFlagKeys(OrderSummary::class));
    }

    /**
     * A change to a row of a table read through an association.
     */
    public function testNativeUpdateOfRelatedRow(): void
    {
        $connection = $this->getConnection();

        $itemId = $this->fetchInt('SELECT item_id FROM "order" ORDER BY id LIMIT 1');
        $categoryId = $this->fetchInt('SELECT id FROM category WHERE id <> (SELECT category_id FROM item WHERE id = ?) ORDER BY id LIMIT 1', [$itemId]);

        $connection->executeStatement('UPDATE item SET category_id = ? WHERE id = ?', [$categoryId, $itemId]);

        $expected = $this->getOrderPartitionKeys('SELECT id FROM "order" WHERE item_id = ?', [$itemId]);

        $this->assertNotSame([], $expected);
        $this->assertSame($expected, $this->getDirtyFlagKeys(OrderSummary::class));
        $this->assertSame($expected, $this->getDirtyFlagKeys(OrderExplicitGroupingSetsSummary::class));
    }

    /**
     * Two hops away, and a source of another summary.
     */
    public function testNativeUpdateTwoHopsAway(): void
    {
        $connection = $this->getConnection();

        $countryId = $this->fetchInt('SELECT country_id FROM customer c JOIN "order" o ON o.customer_id = c.id ORDER BY o.id LIMIT 1');
        $connection->executeStatement('UPDATE country SET region_id = (SELECT MAX(id) FROM region WHERE id <> country.region_id) WHERE id = ?', [$countryId]);

        $this->assertSame(
            $this->getOrderPartitionKeys(
                'SELECT o.id FROM "order" o JOIN customer c ON c.id = o.customer_id WHERE c.country_id = ?',
                [$countryId],
            ),
            $this->getDirtyFlagKeys(OrderSummary::class),
        );

        $this->assertNotSame([], $this->getDirtyFlagKeys(CustomerSummary::class));
    }

    /**
     * The database deletes the orders of the deleted item, without the ORM
     * knowing.
     */
    public function testDatabaseCascade(): void
    {
        $connection = $this->getConnection();

        $constraint = $connection->fetchOne(
            "SELECT conname FROM pg_constraint WHERE conrelid = '\"order\"'::regclass AND contype = 'f' AND pg_get_constraintdef(oid) LIKE '%(item_id)%'",
        );

        $this->assertIsString($constraint);

        $connection->executeStatement(\sprintf(
            'ALTER TABLE "order" DROP CONSTRAINT %1$s, ADD CONSTRAINT %1$s FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE',
            $constraint,
        ));

        $itemId = $this->fetchInt('SELECT item_id FROM "order" ORDER BY id LIMIT 1');
        $expected = $this->getOrderPartitionKeys('SELECT id FROM "order" WHERE item_id = ?', [$itemId]);

        $connection->executeStatement('DELETE FROM item WHERE id = ?', [$itemId]);

        $this->assertNotSame([], $expected);
        $this->assertSame($expected, $this->getDirtyFlagKeys(OrderSummary::class));
    }

    public function testInstallTwice(): void
    {
        $this->getTriggerInstaller()->install();

        $this->assertSame(
            ['account', 'customer', 'empty_entity', 'occupancy_history', 'order'],
            $this->getTablesWithTrigger('rekalogika_dirty_insert'),
        );

        $this->assertSame(
            ['account', 'account_profile', 'country', 'customer', 'empty_entity', 'item', 'occupancy_history', 'order'],
            $this->getTablesWithTrigger('rekalogika_dirty_update'),
        );
    }

    public function testDrop(): void
    {
        $this->getTriggerInstaller()->drop();

        $this->assertSame([], $this->getTablesWithTrigger('rekalogika_dirty_update'));

        $functions = $this->getConnection()->fetchFirstColumn(
            "SELECT proname FROM pg_proc WHERE proname LIKE 'rekalogika\\_dirty\\_%'",
        );

        $this->assertSame([], $functions);

        $this->getConnection()->executeStatement('UPDATE "order" SET time = time WHERE id = 50');
        $this->assertSame(0, $this->countDirtyFlags());
    }

    public function testCommands(): void
    {
        $application = new Application(self::bootKernel());

        $tester = new CommandTester($application->find('rekalogika:analytics:trigger:drop'));
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertSame([], $this->getTablesWithTrigger('rekalogika_dirty_update'));

        $tester = new CommandTester($application->find('rekalogika:analytics:trigger:dump'));
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('-- entity manager: default', $tester->getDisplay());
        $this->assertStringContainsString('CREATE TRIGGER "rekalogika_dirty_update" AFTER UPDATE ON item', $tester->getDisplay());

        $tester = new CommandTester($application->find('rekalogika:analytics:trigger:install'));
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertContains('order', $this->getTablesWithTrigger('rekalogika_dirty_update'));

        $tester = new CommandTester($application->find('rekalogika:analytics:trigger:dump'));
        $this->assertSame(Command::SUCCESS, $tester->execute(['--drop' => true]));
        $this->assertStringContainsString('DROP TRIGGER IF EXISTS "rekalogika_dirty_update" ON "order";', $tester->getDisplay());
        $this->assertStringContainsString('DROP FUNCTION IF EXISTS rekalogika_dirty_order();', $tester->getDisplay());
    }

    public function testCreationTime(): void
    {
        $generator = new TriggerSqlGenerator(
            summaryMetadataFactory: static::getContainer()->get(SummaryMetadataFactory::class),
            sourceMetadataFactory: static::getContainer()->get(SourceMetadataFactory::class),
            managerRegistry: static::getContainer()->get(ManagerRegistry::class),
        );

        $statements = implode("\n", $generator->getCreateStatements()['default'] ?? []);

        $this->assertStringContainsString("DATE_TRUNC('second', NOW())", $statements);
        $this->assertStringNotContainsString('AT TIME ZONE', $statements);
    }

    private function getTriggerInstaller(): TriggerInstaller
    {
        $installer = static::getContainer()->get('rekalogika.analytics.engine.trigger.installer');
        $this->assertInstanceOf(TriggerInstaller::class, $installer);

        return $installer;
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function clearDirtyFlags(): void
    {
        $this->getConnection()->executeStatement('DELETE FROM rekalogika_summary_dirty');
    }

    private function countDirtyFlags(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM rekalogika_summary_dirty');
    }

    /**
     * @param list<mixed> $parameters
     */
    private function fetchInt(string $sql, array $parameters = []): int
    {
        $value = $this->getConnection()->fetchOne($sql, $parameters);
        $this->assertTrue(\is_int($value) || \is_string($value));

        return (int) $value;
    }

    /**
     * @return list<string>
     */
    private function getTablesWithTrigger(string $trigger): array
    {
        /** @var list<string> */
        return $this->getConnection()->fetchFirstColumn(
            "SELECT trim(both '\"' from tgrelid::regclass::text) FROM pg_trigger WHERE tgname = ? ORDER BY 1",
            [$trigger],
        );
    }

    private function getOrderPartitionKey(int $id): int
    {
        return $this->getHandlerFactory()
            ->getSummary(OrderSummary::class)
            ->getPartition()
            ->createLowestPartitionFromSourceValue($id)
            ->getKey();
    }

    /**
     * @param list<mixed> $parameters
     * @return list<int>
     */
    private function getOrderPartitionKeys(string $sql, array $parameters): array
    {
        $keys = [];

        foreach ($this->getConnection()->fetchFirstColumn($sql, $parameters) as $id) {
            $this->assertTrue(\is_int($id) || \is_string($id));
            $keys[] = $this->getOrderPartitionKey((int) $id);
        }

        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    /**
     * @param class-string $summaryClass
     * @return list<int>
     */
    private function getDirtyFlagKeys(string $summaryClass): array
    {
        $keys = [];

        $result = $this->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT key FROM rekalogika_summary_dirty WHERE class = ? AND key IS NOT NULL ORDER BY key',
            [$summaryClass],
        );

        foreach ($result as $key) {
            $this->assertTrue(\is_int($key) || \is_string($key));
            $keys[] = (int) $key;
        }

        return $keys;
    }

    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    private function getOrderCountIn2030(): int
    {
        $result = static::getContainer()->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.year')
            ->getResult();

        $count = $result->find('time.civil.year', '2030')?->getMeasures()->get('count')?->getValue() ?? 0;
        $this->assertIsInt($count);

        return $count;
    }
}
