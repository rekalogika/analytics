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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Doctrine\EventListener\SourceEntityListener;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Tests\App\Entity\Category;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\Region;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

/**
 * A change to a related entity read by a summary through an association
 * refreshes the partitions of the source entities referring to it.
 */
final class RelatedEntityChangeTest extends KernelTestCase
{
    use InteractsWithMessenger;
    use ClockSensitiveTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $connection->executeStatement('DELETE FROM rekalogika_summary_dirty');
        $connection->executeStatement('DELETE FROM rekalogika_summary_refresh_log');
        $connection->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');
    }

    /**
     * `item.category`: moving an item to another category moves its orders.
     */
    public function testModificationOfRelatedEntity(): void
    {
        $clock = self::mockTime();
        $entityManager = $this->getEntityManager();

        $item = $this->findItemWithOrders();
        $oldCategory = $item->getCategory() ?? throw new \RuntimeException('No category');

        $newCategory = $entityManager->find(
            Category::class,
            $this->fetchInt('SELECT id FROM category WHERE id <> ? ORDER BY id LIMIT 1', [$oldCategory->getId()]),
        ) ?? throw new \RuntimeException('Category not found');

        $orderCount = $this->getOrderCountOfItem($item);
        $oldCount = $this->getOrderCountByCategory($oldCategory);
        $newCount = $this->getOrderCountByCategory($newCategory);

        $item->setCategory($newCategory);
        $entityManager->flush();

        $expectedKeys = $this->getOrderPartitionKeys(
            'SELECT id FROM "order" WHERE item_id = ?',
            [$item->getId()],
        );

        $this->assertSame($expectedKeys, $this->getDirtyFlagKeys(OrderSummary::class));
        $this->assertSame($expectedKeys, $this->getDirtyFlagKeys(OrderExplicitGroupingSetsSummary::class));

        $this->runRefreshAgent($clock);

        $this->assertSame($oldCount - $orderCount, $this->getOrderCountByCategory($oldCategory));
        $this->assertSame($newCount + $orderCount, $this->getOrderCountByCategory($newCategory));
    }

    public function testModificationOfUninvolvedProperty(): void
    {
        $item = $this->findItemWithOrders();
        $item->setName('Renamed');

        $this->getEntityManager()->flush();

        $this->assertSame([], $this->getDirtyFlagKeys(OrderSummary::class));
        $this->assertSame([], $this->getDirtyFlagKeys(OrderExplicitGroupingSetsSummary::class));
    }

    /**
     * `customer.country.region` in the order summaries, `country.region` in
     * the customer summary. Two hops from the order.
     */
    public function testModificationTwoHopsAway(): void
    {
        $entityManager = $this->getEntityManager();

        $country = $entityManager->getRepository(Country::class)
            ->findOneBy([], ['id' => 'ASC'])
            ?? throw new \RuntimeException('Country not found');

        $newRegion = $entityManager->find(
            Region::class,
            $this->fetchInt('SELECT id FROM region WHERE id <> ? ORDER BY id LIMIT 1', [$country->getRegion()?->getId()]),
        ) ?? throw new \RuntimeException('Region not found');

        $country->setRegion($newRegion);
        $entityManager->flush();

        $expectedOrderKeys = $this->getOrderPartitionKeys(
            'SELECT o.id FROM "order" o JOIN customer c ON c.id = o.customer_id WHERE c.country_id = ?',
            [$country->getId()],
        );

        $this->assertNotSame([], $expectedOrderKeys);
        $this->assertSame($expectedOrderKeys, $this->getDirtyFlagKeys(OrderSummary::class));
        $this->assertSame($expectedOrderKeys, $this->getDirtyFlagKeys(OrderExplicitGroupingSetsSummary::class));

        $expectedCustomerKeys = $this->getPartitionKeys(
            CustomerSummary::class,
            'SELECT id FROM customer WHERE country_id = ?',
            [$country->getId()],
        );

        $this->assertNotSame([], $expectedCustomerKeys);
        $this->assertSame($expectedCustomerKeys, $this->getDirtyFlagKeys(CustomerSummary::class));
    }

    /**
     * `customer(IndividualCustomer).gender` in the order summaries. Customer
     * is also a source itself, its summary reads `(IndividualCustomer).gender`.
     */
    public function testModificationThroughCast(): void
    {
        $clock = self::mockTime();
        $entityManager = $this->getEntityManager();

        $customerId = $this->getConnection()->fetchOne(
            'SELECT c.id FROM customer c JOIN "order" o ON o.customer_id = c.id WHERE c.gender = ? LIMIT 1',
            [Gender::Male->value],
        );

        $this->assertIsString($customerId);

        $customer = $entityManager->find(IndividualCustomer::class, $customerId)
            ?? throw new \RuntimeException('Customer not found');

        // the orders with id up to 10 are not summarized, see
        // OrderSummary::modifyQueryBuilder()

        $orderCount = $this->fetchInt(
            'SELECT COUNT(*) FROM "order" WHERE customer_id = ? AND id > 10',
            [$customerId],
        );

        $maleCount = $this->getOrderCountByGender(Gender::Male);
        $otherCount = $this->getOrderCountByGender(Gender::Other);

        $customer->setGender(Gender::Other);
        $entityManager->flush();

        $this->assertSame(
            $this->getOrderPartitionKeys('SELECT id FROM "order" WHERE customer_id = ?', [$customerId]),
            $this->getDirtyFlagKeys(OrderSummary::class),
        );

        $this->assertCount(1, $this->getDirtyFlagKeys(CustomerSummary::class));

        $this->runRefreshAgent($clock);

        $this->assertSame($maleCount - $orderCount, $this->getOrderCountByGender(Gender::Male));
        $this->assertSame($otherCount + $orderCount, $this->getOrderCountByGender(Gender::Other));
    }

    /**
     * The deletion of a related entity affects the source entities referring
     * to it, e.g. the database deletes them or sets their reference to null,
     * without the ORM knowing. They are found before the deletion is
     * executed.
     *
     * The test fixtures have no database cascade, so the deletion is only
     * scheduled, without the ORM cascades, and the flush is not executed.
     */
    public function testDeletionOfRelatedEntity(): void
    {
        $entityManager = $this->getEntityManager();

        $country = $entityManager->getRepository(Country::class)
            ->findOneBy([], ['id' => 'ASC'])
            ?? throw new \RuntimeException('Country not found');

        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->scheduleForDelete($country);

        $listener = static::getContainer()->get('rekalogika.analytics.doctrine.source_entity_listener');
        $this->assertInstanceOf(SourceEntityListener::class, $listener);

        $listener->onFlush(new OnFlushEventArgs($entityManager));

        /** @var array<class-string,list<int>> */
        $flags = [];

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof DirtyFlag && $entity->getKey() !== null) {
                $flags[$entity->getClass()][] = $entity->getKey();
            }
        }

        $flags = array_map(function (array $keys): array {
            sort($keys);

            return $keys;
        }, $flags);

        // orders through customer.country and item.countryOfOrigin

        $expectedOrderKeys = $this->getOrderPartitionKeys(
            'SELECT o.id FROM "order" o JOIN customer c ON c.id = o.customer_id WHERE c.country_id = ?
             UNION SELECT o.id FROM "order" o JOIN item i ON i.id = o.item_id WHERE i.country_of_origin_id = ?',
            [$country->getId(), $country->getId()],
        );

        $this->assertSame($expectedOrderKeys, $flags[OrderSummary::class] ?? []);

        // customers through country

        $this->assertSame(
            $this->getPartitionKeys(
                CustomerSummary::class,
                'SELECT id FROM customer WHERE country_id = ?',
                [$country->getId()],
            ),
            $flags[CustomerSummary::class] ?? [],
        );

        $listener->reset();
        $entityManager->clear();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    private function findItemWithOrders(): Item
    {
        $itemId = $this->fetchInt('SELECT item_id FROM "order" WHERE id > 10 ORDER BY id LIMIT 1');

        return $this->getEntityManager()->find(Item::class, $itemId)
            ?? throw new \RuntimeException('Item not found');
    }

    /**
     * The orders with id up to 10 are not summarized, see
     * `OrderSummary::modifyQueryBuilder()`.
     */
    private function getOrderCountOfItem(Item $item): int
    {
        return $this->fetchInt(
            'SELECT COUNT(*) FROM "order" WHERE item_id = ? AND id > 10',
            [$item->getId()],
        );
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
     * @param list<mixed> $parameters
     * @return list<int>
     */
    private function getOrderPartitionKeys(string $sql, array $parameters): array
    {
        return $this->getPartitionKeys(OrderSummary::class, $sql, $parameters);
    }

    /**
     * The lowest partitions of the source entities whose ids are returned by
     * the SQL query.
     *
     * @param class-string $summaryClass
     * @param list<mixed> $parameters
     * @return list<int>
     */
    private function getPartitionKeys(string $summaryClass, string $sql, array $parameters): array
    {
        $partitionHandler = $this->getHandlerFactory()
            ->getSummary($summaryClass)
            ->getPartition();

        $keys = [];

        foreach ($this->getConnection()->fetchFirstColumn($sql, $parameters) as $id) {
            $this->assertTrue(\is_int($id) || \is_string($id));

            $keys[] = $partitionHandler->createLowestPartitionFromSourceValue($id)->getKey();
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

    private function getOrderCountByCategory(Category $category): int
    {
        $id = $category->getId();

        /** @psalm-suppress MixedAssignment */
        $count = static::getContainer()->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('itemCategory')
            ->getResult()
            ->find(
                'itemCategory',
                static fn(mixed $member): bool => $member instanceof Category && $member->getId() === $id,
            )
            ?->getMeasures()
            ->get('count')
            ?->getValue() ?? 0;

        $this->assertIsInt($count);

        return $count;
    }

    private function getOrderCountByGender(Gender $gender): int
    {
        /** @psalm-suppress MixedAssignment */
        $count = static::getContainer()->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerGender')
            ->getResult()
            ->find(
                'customerGender',
                static fn(mixed $member): bool => $member === $gender,
            )
            ?->getMeasures()
            ->get('count')
            ?->getValue() ?? 0;

        $this->assertIsInt($count);

        return $count;
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
