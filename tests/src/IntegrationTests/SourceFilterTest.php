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

use Brick\Money\Money;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Core\Filter\SourceCondition;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\ExpressionUtil;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\SourceExpressionVisitor;
use Rekalogika\Analytics\Engine\SummaryQuery\Expression\SourceFilterExpressionVisitor;
use Rekalogika\Analytics\Metadata\Source\SourceMetadataFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Metadata\Util\SourceFilterUtil;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\FilteredOrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * The filter of a summary restricts the source entities that are summarized,
 * and a change to a property it reads refreshes the summary.
 */
final class SourceFilterTest extends KernelTestCase
{
    use InteractsWithMessenger;
    use ClockSensitiveTrait;

    /**
     * The filter of FilteredOrderSummary in SQL.
     */
    private const FILTER_SQL = <<<'SQL'
        FROM "order" o
        JOIN item i ON i.id = o.item_id
        JOIN customer c ON c.id = o.customer_id
        WHERE o.time >= '2024-01-01' AND i.price >= 5000 AND c.dtype = 'individualcustomer'
        SQL;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $connection->executeStatement('DELETE FROM rekalogika_summary_dirty');
        $connection->executeStatement('DELETE FROM rekalogika_summary_refresh_log');
        $connection->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');
    }

    public function testInvolvedProperties(): void
    {
        $summaryMetadata = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(FilteredOrderSummary::class);

        $paths = $summaryMetadata->getInvolvedSourcePaths();
        $this->assertContains('time', $paths);
        $this->assertContains('item.price', $paths);
        $this->assertContains('customer.*', $paths);

        $properties = $summaryMetadata->getInvolvedSourceProperties();
        $this->assertContains('time', $properties);
        $this->assertContains('item', $properties);
        $this->assertContains('customer', $properties);

        // the price of the item is read through an association

        $dependencies = static::getContainer()
            ->get(SourceMetadataFactory::class)
            ->getSourceMetadata(Item::class)
            ->getAssociationDependenciesByChangedProperties(['price']);

        $summaryClasses = array_map(
            static fn($dependency): string => $dependency->getSummaryClass(),
            $dependencies,
        );

        $this->assertContains(FilteredOrderSummary::class, $summaryClasses);
    }

    public function testRollUpAppliesFilter(): void
    {
        $this->assertSame(
            $this->fetchInt('SELECT COUNT(*) ' . self::FILTER_SQL),
            $this->getFilteredOrderCount(),
        );

        $this->assertGreaterThan(0, $this->getFilteredOrderCount());
        $this->assertLessThan($this->fetchInt('SELECT COUNT(*) FROM "order"'), $this->getFilteredOrderCount());
    }

    public function testSourceEntitiesApplyFilter(): void
    {
        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(FilteredOrderSummary::class)
            ->getResult();

        $ids = [];

        $pageable = $result->getSourceEntities()
            ?? throw new \RuntimeException('No source entities');

        foreach ($pageable->getPages() as $page) {
            foreach ($page as $order) {
                $this->assertInstanceOf(Order::class, $order);
                $ids[] = $order->getId();
            }
        }

        sort($ids);

        $expected = array_map(
            static fn(mixed $id): int => \is_int($id) || \is_string($id) ? (int) $id : 0,
            $this->getConnection()->fetchFirstColumn('SELECT o.id ' . self::FILTER_SQL . ' ORDER BY o.id'),
        );

        $this->assertSame($expected, $ids);
    }

    /**
     * Changing a property only the filter reads moves the order out of the
     * summary.
     */
    public function testModificationOfFilteredProperty(): void
    {
        $clock = self::mockTime();
        $entityManager = $this->getEntityManager();

        $order = $entityManager->find(
            Order::class,
            $this->fetchInt('SELECT o.id ' . self::FILTER_SQL . ' ORDER BY o.id LIMIT 1'),
        ) ?? throw new \RuntimeException('Order not found');

        $initialCount = $this->getFilteredOrderCount();

        $order->setTime(new \DateTimeImmutable('2023-06-01 00:00:00'));
        $entityManager->flush();

        $this->assertSame(1, $this->getDirtyFlagCount(FilteredOrderSummary::class));

        $this->runRefreshAgent($clock);

        $this->assertSame($initialCount - 1, $this->getFilteredOrderCount());
    }

    /**
     * Changing a property of a related entity the filter reads moves the
     * orders referring to it out of the summary.
     */
    public function testModificationOfFilteredRelatedProperty(): void
    {
        $clock = self::mockTime();
        $entityManager = $this->getEntityManager();

        $item = $entityManager->find(
            Item::class,
            $this->fetchInt('SELECT o.item_id ' . self::FILTER_SQL . ' ORDER BY o.id LIMIT 1'),
        ) ?? throw new \RuntimeException('Item not found');

        $initialCount = $this->getFilteredOrderCount();
        $orderCount = $this->fetchInt(
            'SELECT COUNT(*) ' . self::FILTER_SQL . ' AND o.item_id = ?',
            [$item->getId()],
        );

        $this->assertGreaterThan(0, $orderCount);

        $item->setPrice(Money::ofMinor(100, 'EUR'));
        $entityManager->flush();

        $this->assertGreaterThan(0, $this->getDirtyFlagCount(FilteredOrderSummary::class));

        $this->runRefreshAgent($clock);

        $this->assertSame($initialCount - $orderCount, $this->getFilteredOrderCount());
    }

    /**
     * The values are bound without a type, the database converts a string
     * compared with e.g. a UUID or a timestamp.
     *
     * @return iterable<string,array{class-string,Expression,string}>
     */
    public static function provideComparisons(): iterable
    {
        yield 'uuid' => [
            Customer::class,
            new Comparison('id', '>', '01900000-0000-7000-8000-000000000000'),
            "SELECT COUNT(*) FROM customer WHERE id > '01900000-0000-7000-8000-000000000000'",
        ];

        yield 'timestamp' => [
            Order::class,
            new Comparison('time', '<', '2024-01-01 00:00:00'),
            "SELECT COUNT(*) FROM \"order\" WHERE time < '2024-01-01 00:00:00'",
        ];

        yield 'through an association' => [
            Order::class,
            new Comparison('customer.name', '>=', 'M'),
            "SELECT COUNT(*) FROM \"order\" o JOIN customer c ON c.id = o.customer_id WHERE c.name >= 'M'",
        ];

        yield 'in' => [
            Order::class,
            new Comparison('id', 'IN', [11, 12, 13, null]),
            'SELECT COUNT(*) FROM "order" WHERE id IN (11, 12, 13)',
        ];

        yield 'not in' => [
            Order::class,
            new Comparison('id', 'NIN', [11, 12, 13]),
            'SELECT COUNT(*) FROM "order" WHERE id NOT IN (11, 12, 13)',
        ];

        yield 'is null' => [
            Order::class,
            new Comparison('shipped', '=', null),
            'SELECT COUNT(*) FROM "order" WHERE shipped IS NULL',
        ];

        yield 'contains' => [
            Customer::class,
            new Comparison('name', 'CONTAINS', 'a'),
            "SELECT COUNT(*) FROM customer WHERE name LIKE '%a%'",
        ];

        yield 'starts with' => [
            Customer::class,
            new Comparison('name', 'STARTS_WITH', 'A'),
            "SELECT COUNT(*) FROM customer WHERE name LIKE 'A%'",
        ];

        yield 'ends with' => [
            Customer::class,
            new Comparison('name', 'ENDS_WITH', 'a'),
            "SELECT COUNT(*) FROM customer WHERE name LIKE '%a'",
        ];

        yield 'like wildcards are escaped' => [
            Customer::class,
            new Comparison('name', 'CONTAINS', '%'),
            "SELECT COUNT(*) FROM customer WHERE name LIKE '%\\%%'",
        ];

        yield 'not' => [
            Order::class,
            new CompositeExpression(CompositeExpression::TYPE_NOT, [
                new Comparison('id', '<=', 10),
            ]),
            'SELECT COUNT(*) FROM "order" WHERE NOT (id <= 10)',
        ];

        yield 'or with a source condition' => [
            Order::class,
            new CompositeExpression(CompositeExpression::TYPE_OR, [
                new Comparison('id', '<=', 10),
                new SourceCondition('[time] > [shipped]'),
            ]),
            'SELECT COUNT(*) FROM "order" WHERE id <= 10 OR time > shipped',
        ];

        yield 'source condition alone' => [
            Order::class,
            new SourceCondition('[customer.*] INSTANCE OF Rekalogika\Analytics\Tests\App\Entity\OrganizationalCustomer'),
            "SELECT COUNT(*) FROM \"order\" o JOIN customer c ON c.id = o.customer_id WHERE c.dtype = 'organizationalcustomer'",
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideComparisons')]
    public function testComparison(string $class, Expression $filter, string $sql): void
    {
        $summaryClass = $class === Customer::class ? CustomerSummary::class : OrderSummary::class;

        $summaryMetadata = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata($summaryClass);

        $queryBuilder = new SimpleQueryBuilder(
            entityManager: $this->getEntityManager(),
            from: $class,
            alias: 'root',
        );

        ExpressionUtil::addExpressionToQueryBuilder(
            metadata: $summaryMetadata,
            queryBuilder: $queryBuilder,
            expression: $filter,
            visitorClass: SourceFilterExpressionVisitor::class,
        );

        /** @psalm-suppress MixedAssignment */
        $count = $queryBuilder
            ->select('COUNT(root)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertSame($this->fetchInt($sql), \is_int($count) || \is_string($count) ? (int) $count : null);
    }

    public function testUnsupportedOperator(): void
    {
        $this->expectException(MetadataException::class);

        SourceFilterUtil::getInvolvedProperties(new Comparison('orders', Comparison::MEMBER_OF, 1));
    }

    public function testSourceConditionOutsideFilter(): void
    {
        $summaryMetadata = static::getContainer()
            ->get(SummaryMetadataFactory::class)
            ->getSummaryMetadata(OrderSummary::class);

        $this->expectException(LogicException::class);

        ExpressionUtil::addExpressionToQueryBuilder(
            metadata: $summaryMetadata,
            queryBuilder: new SimpleQueryBuilder(
                entityManager: $this->getEntityManager(),
                from: Order::class,
                alias: 'root',
            ),
            expression: new SourceCondition('[time] > [shipped]'),
            visitorClass: SourceExpressionVisitor::class,
        );
    }

    private function getFilteredOrderCount(): int
    {
        /** @psalm-suppress MixedAssignment */
        $count = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(FilteredOrderSummary::class)
            ->getResult()
            ->getMeasures()
            ->get('count')
            ?->getValue() ?? 0;

        $this->assertIsInt($count);

        return $count;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
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
     * @param class-string $summaryClass
     */
    private function getDirtyFlagCount(string $summaryClass): int
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM rekalogika_summary_dirty WHERE class = ?',
            [$summaryClass],
        );
    }

    /**
     * Runs the refresh agents scheduled by the flush, after the minimum age
     * of the dirty flags. The timestamps in the database are based on the
     * wall clock, so they are backdated.
     */
    private function runRefreshAgent(ClockInterface $clock): void
    {
        $this->getEntityManager()->clear();

        $transport = $this->transport('async');
        $transport->process();

        $clock->sleep(600);

        $connection = $this->getConnection();
        $connection->executeStatement("UPDATE rekalogika_summary_dirty SET created = created - interval '600 seconds'");
        $connection->executeStatement("UPDATE rekalogika_summary_properties SET scheduled_refresh = scheduled_refresh - interval '600 seconds'");

        $transport->process();
        $transport->queue()->assertEmpty();
    }
}
