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

use Doctrine\Common\Collections\Order as DoctrineOrder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Entity\PartitionRange;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\DefaultRefreshAgentStrategy;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Tests\App\Entity\Country;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\Gender;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\OccupancyHistory;
use Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\EventListener\TestNewDirtyFlagListener;
use Rekalogika\Analytics\Uuid\Partition\UuidV7IntegerPartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class SourceChangeTest extends KernelTestCase
{
    use InteractsWithMessenger;
    use ClockSensitiveTrait;

    /**
     * Sequences are not affected by transactions, so we need to reset them
     * manually
     */
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = static::getContainer()
            ->get(Connection::class);

        $sql = "SELECT setval(pg_get_serial_sequence('order', 'id'), 201) FROM \"order\"";

        $connection->executeStatement($sql);
    }

    /**
     * Clears the refresh agent state left by previous runs.
     */
    private function clearRefreshState(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        $connection->executeStatement('DELETE FROM rekalogika_summary_dirty');
        $connection->executeStatement('DELETE FROM rekalogika_summary_refresh_log');
        $connection->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');
    }

    /**
     * Simulates the passing of time. The mock clock affects the messenger
     * delays, but the timestamps in the database are based on the wall clock,
     * so we backdate them.
     */
    private function travel(ClockInterface $clock, int $seconds): void
    {
        $clock->sleep($seconds);

        $connection = static::getContainer()->get(Connection::class);

        $connection->executeStatement(
            'UPDATE rekalogika_summary_dirty SET created = created - make_interval(secs => ?)',
            [$seconds],
        );

        $connection->executeStatement(
            'UPDATE rekalogika_summary_refresh_log SET refreshed = refreshed - make_interval(secs => ?)',
            [$seconds],
        );

        $connection->executeStatement(
            'UPDATE rekalogika_summary_properties SET scheduled_refresh = scheduled_refresh - make_interval(secs => ?)',
            [$seconds],
        );
    }

    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    private function modifyOrderTime(int $id, \DateTimeImmutable $time): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $order = $entityManager
            ->getRepository(Order::class)
            ->find($id)
            ?? throw new \RuntimeException('Order not found');

        $order->setTime($time);

        $entityManager->flush();
        $entityManager->clear();
    }

    private function getOrderCount(): int
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManager::class);

        $result = $summaryManager->createQuery()
            ->from(OrderSummary::class)
            ->getResult();

        $count = $result->getMeasures()->get('count')?->getValue();
        $this->assertIsInt($count);

        return $count;
    }

    private function getOrderCountIn2030(): int
    {
        $summaryManager = static::getContainer()->get(SummaryManager::class);

        $result = $summaryManager->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.year')
            ->getResult();

        $count = $result->find('time.civil.year', '2030')?->getMeasures()->get('count')?->getValue() ?? 0;
        $this->assertIsInt($count);

        return $count;
    }

    public function testSourceCreation(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // clear existing dirty flags & refresh agent state if exists

        $this->clearRefreshState();

        // get the current result

        $initialCount = $this->getOrderCount();

        // create new order

        $item = $entityManager
            ->getRepository(Item::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Customer not found');

        $time = $clock->now();

        $order = new Order();
        $order->setItem($item);
        $order->setCustomer($customer);
        $order->setTime($time);
        $order->setShipped($time->modify('+3 days'));

        // persist and flush

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        // check dirty flags: a new entity flag, and the flag of the lowest
        // partition containing the new order

        $repository = $entityManager->getRepository(DirtyFlag::class);

        $this->assertCount(2, $repository->findBy(['class' => OrderSummary::class]));

        $newFlag = $repository->findOneBy(['class' => OrderSummary::class, 'level' => null]);
        $this->assertNotNull($newFlag);
        $this->assertNull($newFlag->getKey());

        $partition = $this->getHandlerFactory()
            ->getSummary(OrderSummary::class)
            ->getPartition()
            ->getLowestPartitionFromEntity($order);

        $this->assertNotNull($repository->findOneBy([
            'class' => OrderSummary::class,
            'level' => $partition->getLevel(),
            'key' => $partition->getKey(),
        ]));

        // check if NewDirtyFlagEvent is emitted

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $events = array_filter(
            $listener->getEvents(),
            static fn(NewDirtyFlagEvent $event): bool => $event->getDirtyFlag()->getClass() === OrderSummary::class,
        );
        $this->assertCount(2, $events);

        // check messenger now, it should have one pending message for each
        // summary of Order: OrderSummary and OrderExplicitGroupingSetsSummary

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(2);

        // another change before the scheduled run should not dispatch another
        // message, the scheduled run will process it

        $this->travel($clock, 100);

        $item = $entityManager->find(Item::class, $item->getId())
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager->find(Customer::class, $customer->getId())
            ?? throw new \RuntimeException('Customer not found');

        $order = new Order();
        $order->setItem($item);
        $order->setCustomer($customer);
        $order->setTime($time);
        $order->setShipped($time->modify('+3 days'));

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        $transport->process();
        $transport->queue()->assertCount(2);

        // the refresh agent is scheduled to run when the first dirty flag
        // satisfies the minimum age of the refresh agent strategy, 600 seconds.

        $this->travel($clock, 499);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 600th second it should process the message. the
        // creation times of the new records are preserved, and the minimum
        // idle delay of 300 seconds is satisfied, so both records are
        // processed and the refresh agent does not reschedule itself.

        $this->travel($clock, 1);
        $transport->process();
        $transport->queue()->assertCount(0);

        // the count should be updated now

        $this->assertEquals($initialCount + 2, $this->getOrderCount());

        // dump($transport->queue()->messages());
    }

    public function testSourceModification(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // clear existing dirty flags & refresh agent state if exists

        $this->clearRefreshState();

        // get the current result

        $this->assertEquals(0, $this->getOrderCountIn2030());

        // get one order and modify the time to 2030

        $order = $entityManager
            ->getRepository(Order::class)
            ->find(50)
            ?? throw new \RuntimeException('Order not found');

        $order->setTime(new \DateTimeImmutable('2030-02-01 00:00:00'));

        // flush

        $entityManager->flush();
        $entityManager->clear();

        // check dirty flag

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $dirtyFlag = $dirtyFlags[0];
        $this->assertEquals(OrderSummary::class, $dirtyFlag->getClass());
        $this->assertNotNull($dirtyFlag->getKey());
        $this->assertNotNull($dirtyFlag->getLevel());

        // check if NewDirtyFlagEvent is emitted

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $events = array_filter(
            $listener->getEvents(),
            static fn(NewDirtyFlagEvent $event): bool => $event->getDirtyFlag()->getClass() === OrderSummary::class,
        );
        $this->assertCount(1, $events);

        // check messenger now, it should have one pending message for each
        // summary of Order: OrderSummary and OrderExplicitGroupingSetsSummary

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(2);

        // the refresh agent is scheduled to run when the dirty flag satisfies
        // the minimum age of the refresh agent strategy, 600 seconds.

        $this->travel($clock, 599);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 600th second it should process the message, and the
        // refresh agent should not reschedule itself because there are no
        // more dirty flags

        $this->travel($clock, 1);
        $transport->process();
        $transport->queue()->assertCount(0);

        // we check the result

        $this->assertEquals(1, $this->getOrderCountIn2030());
    }

    public function testSourceDeletion(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // clear existing dirty flags & refresh agent state if exists

        $this->clearRefreshState();

        // get the current result

        $initialCount = $this->getOrderCount();

        // get one order and remove it

        $order = $entityManager
            ->getRepository(Order::class)
            ->find(50)
            ?? throw new \RuntimeException('Order not found');

        $entityManager->remove($order);

        // flush

        $entityManager->flush();
        $entityManager->clear();

        // check dirty flag

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $dirtyFlag = $dirtyFlags[0];
        $this->assertEquals(OrderSummary::class, $dirtyFlag->getClass());
        $this->assertNotNull($dirtyFlag->getKey());
        $this->assertNotNull($dirtyFlag->getLevel());

        // check if NewDirtyFlagEvent is emitted

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $events = array_filter(
            $listener->getEvents(),
            static fn(NewDirtyFlagEvent $event): bool => $event->getDirtyFlag()->getClass() === OrderSummary::class,
        );
        $this->assertCount(1, $events);

        // check messenger now, it should have one pending message for each
        // summary of Order: OrderSummary and OrderExplicitGroupingSetsSummary

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(2);

        // the refresh agent is scheduled to run when the dirty flag satisfies
        // the minimum age of the refresh agent strategy, 600 seconds.

        $this->travel($clock, 599);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 600th second it should process the message, and the
        // refresh agent should not reschedule itself because there are no
        // more dirty flags

        $this->travel($clock, 1);
        $transport->process();
        $transport->queue()->assertCount(0);

        // we check the result

        $this->assertEquals($initialCount - 1, $this->getOrderCount());
    }

    public function testUpperPartitionCooldown(): void
    {
        $clock = self::mockTime();
        $this->clearRefreshState();
        $transport = $this->transport('async');

        $this->assertEquals(0, $this->getOrderCountIn2030());

        // the lowest partitions of TestIntegerPartition contain 4 records,
        // and the upper partitions contain 4 partitions of the level below.
        // order 50 & 40 are in different partitions in the lowest two
        // levels, but in the same partition in the third level, [0, 64).
        // queries use the third level for both orders.

        // t=0: modify order 50

        $this->modifyOrderTime(50, new \DateTimeImmutable('2030-02-01 00:00:00'));
        $transport->process();
        $transport->queue()->assertCount(2);

        // t=200: modify order 40, the existing scheduled run will process it

        $this->travel($clock, 200);
        $this->modifyOrderTime(40, new \DateTimeImmutable('2030-02-01 00:00:00'));
        $transport->process();
        $transport->queue()->assertCount(2);

        // t=600: the change to order 50 is refreshed, and propagated up
        // immediately. the change to order 40 is not old enough, the refresh
        // agent reschedules itself at t=800

        $this->travel($clock, 400);
        $transport->process();
        $transport->queue()->assertCount(2);
        $this->assertEquals(1, $this->getOrderCountIn2030());

        // t=800: the change to order 40 is refreshed, but the third level
        // partition is still in the cooldown period after it was refreshed at
        // t=600. the refresh agent reschedules itself at t=900

        $this->travel($clock, 200);
        $transport->process();
        $transport->queue()->assertCount(2);
        $this->assertEquals(1, $this->getOrderCountIn2030());

        // t=900: the cooldown period ends, the third level partition is
        // refreshed

        $this->travel($clock, 100);
        $transport->process();
        $transport->queue()->assertCount(0);
        $this->assertEquals(2, $this->getOrderCountIn2030());
    }

    /**
     * If the latest summarized key is in the last child of an upper
     * partition, queries read the upper partition. A change in an earlier
     * child of the upper partition must be propagated up to it.
     */
    public function testModificationInEarlierChildOfPartiallyCoveredUpperPartition(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $refresherFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $refresherFactory);

        $strategy = new DefaultRefreshAgentStrategy(
            minimumAge: null,
            minimumIdleDelay: null,
            maximumAge: null,
            upperPartitionCooldown: null,
        );

        // the lowest partitions of TestIntegerPartition contain 4 records,
        // and the upper partitions contain 4 partitions of the level below.
        // a new order with id 204 moves the latest key to [204, 208), the
        // last child of the second level partition [192, 208). queries then
        // read [192, 208) instead of its children.

        static::getContainer()
            ->get(Connection::class)
            ->executeStatement("SELECT setval(pg_get_serial_sequence('order', 'id'), 203)");

        $item = $entityManager
            ->getRepository(Item::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Customer not found');

        $time = new \DateTimeImmutable('2024-06-01 00:00:00');

        $order = new Order();
        $order->setItem($item);
        $order->setCustomer($customer);
        $order->setTime($time);
        $order->setShipped($time->modify('+3 days'));

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        $this->assertSame(204, $order->getId());

        $refresherFactory
            ->createSummaryRefresher(OrderSummary::class)
            ->refresh($strategy);

        $this->assertEquals(0, $this->getOrderCountIn2030());

        // modify order 194, in [192, 196), an earlier child of [192, 208)

        $this->modifyOrderTime(194, new \DateTimeImmutable('2030-02-01 00:00:00'));

        $refresherFactory
            ->createSummaryRefresher(OrderSummary::class)
            ->refresh($strategy);

        $this->assertEquals(1, $this->getOrderCountIn2030());
    }

    /**
     * A manual refresh without a range must process a single new record, even
     * if the computed start and end are the same key.
     */
    /**
     * New records are found by comparing the latest summarized key with the
     * latest key in the source. A record whose transaction commits after a
     * refresh has moved the latest key past its key, e.g. a long-running
     * import holding lower ids, is therefore not new by that comparison. The
     * dirty flag of its partition, committed together with the record, must
     * still get it summarized.
     */
    public function testRecordCommittedAfterLatestKeyIsSummarized(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $initialCount = $this->getOrderCount();

        $item = $entityManager
            ->getRepository(Item::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Customer not found');

        $time = new \DateTimeImmutable('2024-06-01 00:00:00');

        $order = new Order();
        $order->setItem($item);
        $order->setCustomer($customer);
        $order->setTime($time);
        $order->setShipped($time->modify('+3 days'));

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        $orderId = $order->getId();
        $this->assertNotNull($orderId);

        // simulate a refresh that ran while the order was not yet visible,
        // and moved the latest key past it

        $this->getHandlerFactory()
            ->getSummary(OrderSummary::class)
            ->updateLatestKey($orderId);

        $refresherFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $refresherFactory);

        $refresherFactory
            ->createSummaryRefresher(OrderSummary::class)
            ->refresh(new DefaultRefreshAgentStrategy(
                minimumAge: null,
                minimumIdleDelay: null,
                maximumAge: null,
                upperPartitionCooldown: null,
            ));

        $this->assertEquals($initialCount + 1, $this->getOrderCount());
    }

    /**
     * @return iterable<string,array{DoctrineOrder}>
     */
    public static function provideOrder(): iterable
    {
        yield 'ascending' => [DoctrineOrder::Ascending];
        yield 'descending' => [DoctrineOrder::Descending];
    }

    /**
     * An order that is not shipped yet has a null member in the shipped date
     * dimension, a sequence dimension. The drill-down must still include it,
     * and the gap filling must still happen.
     */
    #[DataProvider('provideOrder')]
    public function testNullMemberOfSequenceDimension(DoctrineOrder $order): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $item = $entityManager
            ->getRepository(Item::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Customer not found');

        $unshipped = new Order();
        $unshipped->setItem($item);
        $unshipped->setCustomer($customer);
        $unshipped->setTime(new \DateTimeImmutable('2024-06-01 00:00:00'));

        $entityManager->persist($unshipped);
        $entityManager->flush();
        $entityManager->clear();

        static::getContainer()
            ->get(SummaryManager::class)
            ->refresh(OrderSummary::class, null, null, 1);

        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('shippedDate')
            ->orderBy('shippedDate', $order)
            ->getResult();

        $total = $result->getMeasures()->get('count')?->getValue();
        $this->assertIsInt($total);

        $sum = 0;
        $members = [];

        foreach ($result->drillDown('shippedDate') as $cell) {
            $dimension = $cell->getCoordinates()->get('shippedDate');
            $this->assertNotNull($dimension);

            /** @psalm-suppress MixedAssignment */
            $members[] = $dimension->getMember();

            $count = $cell->getMeasures()->get('count')?->getValue() ?? 0;
            $this->assertIsInt($count);
            $sum += $count;
        }

        $this->assertSame($total, $sum);

        // the null member is at the end where it sorts
        if ($order === DoctrineOrder::Ascending) {
            $this->assertNull(array_pop($members));
        } else {
            $this->assertNull(array_shift($members));
        }

        // the other members are a gapless sequence
        $previous = null;

        foreach ($members as $member) {
            $this->assertInstanceOf(SequenceMember::class, $member);

            if ($previous !== null) {
                $this->assertSame(
                    $order === DoctrineOrder::Ascending
                        ? $previous->getNext()
                        : $previous->getPrevious(),
                    $member,
                );
            }

            $previous = $member;
        }
    }

    /**
     * The partitioning key of OccupancyHistorySummary is a date, not a string
     * or an integer. Inserting a record must still create the dirty flag of
     * its partition.
     */
    public function testCreationWithDatePartitioningKey(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $occupancyHistory = new OccupancyHistory();
        $occupancyHistory->setDate(new \DateTimeImmutable('2024-06-01'));
        $occupancyHistory->setGender(Gender::Male);
        $occupancyHistory->setCount(1);

        $entityManager->persist($occupancyHistory);
        $entityManager->flush();
        $entityManager->clear();

        $partition = $this->getHandlerFactory()
            ->getSummary(OccupancyHistorySummary::class)
            ->getPartition()
            ->getLowestPartitionFromEntity($occupancyHistory);

        $this->assertNotNull($entityManager->getRepository(DirtyFlag::class)->findOneBy([
            'class' => OccupancyHistorySummary::class,
            'level' => $partition->getLevel(),
            'key' => $partition->getKey(),
        ]));
    }

    private function getOccupancyCount(string $date): int
    {
        $count = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OccupancyHistorySummary::class)
            ->withDimensions('date')
            ->getResult()
            ->find('date', $date)
            ?->getMeasures()
            ->get('count')
            ?->getValue()
            ?? 0;

        $this->assertIsInt($count);

        return $count;
    }

    /**
     * Modifying the partitioning key moves the entity to another partition.
     * Both the partition it was in, and the partition it is moved to, must be
     * refreshed.
     */
    public function testPartitionKeyModification(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $occupancyHistory = $entityManager
            ->getRepository(OccupancyHistory::class)
            ->findOneBy([], ['id' => 'ASC'])
            ?? throw new \RuntimeException('Occupancy history not found');

        $oldDate = \DateTimeImmutable::createFromInterface(
            $occupancyHistory->getDate()
                ?? throw new \RuntimeException('Date not found'),
        );

        $newDate = $oldDate->modify('+1 day');
        $count = $occupancyHistory->getCount();
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);

        $initialOldDateCount = $this->getOccupancyCount($oldDate->format('Y-m-d'));
        $initialNewDateCount = $this->getOccupancyCount($newDate->format('Y-m-d'));

        // move the record to the next day, which is in another partition

        $partitionHandler = $this->getHandlerFactory()
            ->getSummary(OccupancyHistorySummary::class)
            ->getPartition();

        $oldPartition = $partitionHandler->getLowestPartitionFromEntity($occupancyHistory);
        $occupancyHistory->setDate($newDate);
        $newPartition = $partitionHandler->getLowestPartitionFromEntity($occupancyHistory);

        $this->assertNotSame($oldPartition->getKey(), $newPartition->getKey());

        $entityManager->flush();
        $entityManager->clear();

        // both partitions are flagged

        $repository = $entityManager->getRepository(DirtyFlag::class);

        foreach ([$oldPartition, $newPartition] as $partition) {
            $this->assertNotNull($repository->findOneBy([
                'class' => OccupancyHistorySummary::class,
                'level' => $partition->getLevel(),
                'key' => $partition->getKey(),
            ]));
        }

        // refresh, both dates must be updated

        $refresherFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $refresherFactory);

        $refresherFactory
            ->createSummaryRefresher(OccupancyHistorySummary::class)
            ->refresh(new DefaultRefreshAgentStrategy(
                minimumAge: null,
                minimumIdleDelay: null,
                maximumAge: null,
                upperPartitionCooldown: null,
            ));

        $this->assertSame(
            $initialOldDateCount - $count,
            $this->getOccupancyCount($oldDate->format('Y-m-d')),
        );

        $this->assertSame(
            $initialNewDateCount + $count,
            $this->getOccupancyCount($newDate->format('Y-m-d')),
        );
    }

    /**
     * The dirty flags of a flush that failed are rolled back with it, and must
     * not be carried over to the next flush.
     */
    public function testFailedFlushDoesNotLeaveDirtyFlags(): void
    {
        $this->clearRefreshState();

        $managerRegistry = static::getContainer()->get('doctrine');
        $this->assertInstanceOf(ManagerRegistry::class, $managerRegistry);

        $entityManager = $managerRegistry->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        // a flush that fails: the record is moved to another date, and a new
        // record without the required fields fails to insert

        $occupancyHistory = $entityManager
            ->getRepository(OccupancyHistory::class)
            ->findOneBy([], ['id' => 'ASC'])
            ?? throw new \RuntimeException('Occupancy history not found');

        $occupancyHistory->setDate(new \DateTimeImmutable('2099-01-01'));

        $failedPartition = $this->getHandlerFactory()
            ->getSummary(OccupancyHistorySummary::class)
            ->getPartition()
            ->getLowestPartitionFromEntity($occupancyHistory);

        $entityManager->persist(new OccupancyHistory());

        try {
            $entityManager->flush();
            $this->fail('The flush should have failed');
        } catch (DriverException) {
        }

        $managerRegistry->resetManager();
        $entityManager = $managerRegistry->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        // a successful flush

        $occupancyHistory = $entityManager
            ->getRepository(OccupancyHistory::class)
            ->findOneBy([], ['id' => 'DESC'])
            ?? throw new \RuntimeException('Occupancy history not found');

        $occupancyHistory->setCount((int) $occupancyHistory->getCount() + 1);

        $entityManager->flush();
        $entityManager->clear();

        // only the flag of the successful flush is created

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OccupancyHistorySummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $this->assertNotSame($failedPartition->getKey(), $dirtyFlags[0]->getKey());
    }

    public function testManualRefreshOfSingleNewRecord(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $summaryManager = static::getContainer()
            ->get(SummaryManager::class);

        $initialCount = $this->getOrderCount();

        // make sure the new order gets the key right after the latest one

        static::getContainer()
            ->get(Connection::class)
            ->executeStatement("SELECT setval(pg_get_serial_sequence('order', 'id'), (SELECT max(id) FROM \"order\"))");

        $item = $entityManager
            ->getRepository(Item::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Item not found');

        $customer = $entityManager
            ->getRepository(Customer::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Customer not found');

        $time = new \DateTimeImmutable('2024-06-01 00:00:00');

        $order = new Order();
        $order->setItem($item);
        $order->setCustomer($customer);
        $order->setTime($time);
        $order->setShipped($time->modify('+3 days'));

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        $this->assertSame(201, $order->getId());

        $summaryManager->refresh(OrderSummary::class, null, null);

        $this->assertEquals($initialCount + 1, $this->getOrderCount());

        // nothing new, refreshing again must not change anything

        $summaryManager->refresh(OrderSummary::class, null, null);

        $this->assertEquals($initialCount + 1, $this->getOrderCount());
    }

    /**
     * The bounds of a manual refresh are inclusive, so a range with the same
     * start and end processes that key.
     */
    public function testManualRefreshOfSingleKeyRange(): void
    {
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $summaryManager = static::getContainer()
            ->get(SummaryManager::class);

        // modifying order 50 marks its lowest-level partition as dirty

        $this->modifyOrderTime(50, new \DateTimeImmutable('2030-02-01 00:00:00'));

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $lowestLevel = $dirtyFlags[0]->getLevel();
        $entityManager->clear();

        // refreshing the range [50, 50] refreshes the lowest-level partition,
        // which removes its dirty flag. the range does not cover the whole
        // containing partition, so the containing partition is marked dirty

        $summaryManager->refresh(OrderSummary::class, 50, 50);

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $this->assertNotSame($lowestLevel, $dirtyFlags[0]->getLevel());
    }

    /**
     * The latest key of a UUID-partitioned summary is a UUID string, while
     * the partition bounds are integers. Refreshing an old partition that is
     * not the last child of its containing partition must still mark the
     * containing partition as dirty.
     */
    public function testUuidPartitionRefreshMarksUpperPartitionDirty(): void
    {
        $this->clearRefreshState();

        $refresher = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $refresher);

        $refresher = $refresher->createSummaryRefresher(CustomerSummary::class);

        // the first lowest-level partition of the level-27 partition
        // containing 2020-01-01, well before the latest summarized customer

        $key = intdiv(1577836800000, 1 << 27) * (1 << 27);
        $partition = UuidV7IntegerPartition::createFromSourceValue($key, 22);
        $upperPartition = $partition->getContaining();

        $this->assertNotNull($upperPartition);
        $this->assertNotSame($upperPartition->getUpperBound(), $partition->getUpperBound());

        $dirtyFlags = $refresher->refreshPartition($partition);

        $this->assertCount(1, $dirtyFlags);
        $this->assertSame(CustomerSummary::class, $dirtyFlags[0]->getClass());
        $this->assertSame($upperPartition->getLevel(), $dirtyFlags[0]->getLevel());
        $this->assertSame($upperPartition->getKey(), $dirtyFlags[0]->getKey());
    }

    /**
     * A change made outside the ORM is not detected. `markDirty()` makes the
     * refresh agent pick it up.
     */
    public function testMarkDirty(): void
    {
        $clock = self::mockTime();
        $this->clearRefreshState();

        $this->assertEquals(0, $this->getOrderCountIn2030());

        $connection = static::getContainer()->get(Connection::class);

        $connection->executeStatement(
            'UPDATE "order" SET time = ? WHERE id = ?',
            ['2030-02-01 00:00:00', 50],
        );

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertCount(0, $entityManager->getRepository(DirtyFlag::class)->findAll());

        static::getContainer()
            ->get(SummaryManager::class)
            ->markDirty(OrderSummary::class, [50, '50']);

        $expectedPartition = $this->getHandlerFactory()
            ->getSummary(OrderSummary::class)
            ->getPartition()
            ->createLowestPartitionFromSourceValue(50);

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $this->assertSame($expectedPartition->getLevel(), $dirtyFlags[0]->getLevel());
        $this->assertSame($expectedPartition->getKey(), $dirtyFlags[0]->getKey());

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $events = array_filter(
            $listener->getEvents(),
            static fn(NewDirtyFlagEvent $event): bool => $event->getDirtyFlag()->getClass() === OrderSummary::class,
        );
        $this->assertCount(1, $events);

        // only OrderSummary is scheduled

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(1);

        $this->travel($clock, 600);
        $transport->process();
        $transport->queue()->assertCount(0);

        $this->assertEquals(1, $this->getOrderCountIn2030());
    }

    public function testMarkDirtyWithoutKeys(): void
    {
        $this->clearRefreshState();

        static::getContainer()
            ->get(SummaryManager::class)
            ->markDirty(OrderSummary::class, []);

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertCount(0, $entityManager->getRepository(DirtyFlag::class)->findAll());
        $this->transport('async')->queue()->assertCount(0);
    }

    public function testMarkDirtyRange(): void
    {
        $this->clearRefreshState();

        static::getContainer()
            ->get(SummaryManager::class)
            ->markDirtyRange(OrderSummary::class, 1, 200);

        $partitionHandler = $this->getHandlerFactory()
            ->getSummary(OrderSummary::class)
            ->getPartition();

        $expectedKeys = [];

        foreach (new PartitionRange(
            $partitionHandler->createLowestPartitionFromSourceValue(1),
            $partitionHandler->createLowestPartitionFromSourceValue(200),
        ) as $partition) {
            $expectedKeys[] = $partition->getKey();
        }

        $this->assertGreaterThan(1, \count($expectedKeys));

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class], ['key' => 'ASC']);

        $this->assertSame(
            $expectedKeys,
            array_map(static fn(DirtyFlag $flag): ?int => $flag->getKey(), $dirtyFlags),
        );

        $this->transport('async')->queue()->assertCount(1);
    }

    public function testMarkDirtyRangeWithReversedKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        static::getContainer()
            ->get(SummaryManager::class)
            ->markDirtyRange(OrderSummary::class, 200, 1);
    }

    /**
     * A dimension reading a property of a subclass through a cast of the root
     * entity, `(IndividualCustomer).gender`, must be refreshed when the
     * property changes.
     */
    public function testRootCastPropertyModification(): void
    {
        $clock = self::mockTime();
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // a customer with orders, the order summaries read the gender of the
        // customer, and are refreshed too

        $customer = $entityManager->createQueryBuilder()
            ->select('c')
            ->from(IndividualCustomer::class, 'c')
            ->where('c.gender = :gender')
            ->andWhere(\sprintf('EXISTS (SELECT 1 FROM %s o WHERE o.customer = c)', Order::class))
            ->setParameter('gender', Gender::Male)
            ->orderBy('c.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ?? throw new \RuntimeException('Customer not found');

        $this->assertInstanceOf(IndividualCustomer::class, $customer);

        $initialMale = $this->getCustomerCountByGender(Gender::Male);
        $initialOther = $this->getCustomerCountByGender(Gender::Other);

        $customer->setGender(Gender::Other);

        $entityManager->flush();
        $entityManager->clear();

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => CustomerSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $this->assertNotNull($dirtyFlags[0]->getKey());

        $this->runCustomerSummaryRefreshAgent($clock);

        $this->assertSame($initialMale - 1, $this->getCustomerCountByGender(Gender::Male));
        $this->assertSame($initialOther + 1, $this->getCustomerCountByGender(Gender::Other));
    }

    private function getCustomerCountByGender(Gender $gender): int
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManager::class);

        /** @psalm-suppress MixedAssignment */
        $count = $summaryManager->createQuery()
            ->from(CustomerSummary::class)
            ->withDimensions('gender')
            ->getResult()
            ->find(
                'gender',
                static fn(mixed $member): bool => $member === $gender,
            )
            ?->getMeasures()
            ->get('count')
            ?->getValue() ?? 0;

        $this->assertIsInt($count);

        return $count;
    }

    private function getCustomerCount(?Country $country = null): int
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManager::class);

        $query = $summaryManager->createQuery()
            ->from(CustomerSummary::class);

        if ($country === null) {
            $count = $query->getResult()->getMeasures()->get('count')?->getValue();
        } else {
            $id = $country->getId();

            /** @psalm-suppress MixedAssignment */
            $count = $query
                ->withDimensions('country')
                ->getResult()
                ->find(
                    'country',
                    static fn(mixed $member): bool => $member instanceof Country
                        && $member->getId() === $id,
                )
                ?->getMeasures()
                ->get('count')
                ?->getValue() ?? 0;
        }

        $this->assertIsInt($count);

        return $count;
    }

    /**
     * Runs the refresh agent of CustomerSummary from the time a customer is
     * modified. The summaries of the orders of the customer may also be
     * refreshed, they read the properties of the customer.
     */
    private function runCustomerSummaryRefreshAgent(ClockInterface $clock): void
    {
        $transport = $this->transport('async');
        $transport->process();

        $summaryClasses = array_map(
            static fn(object $message): string => $message instanceof RefreshAgentStartCommand
                ? $message->getSummaryClass()
                : $message::class,
            $transport->queue()->messages(),
        );

        $this->assertContains(CustomerSummary::class, $summaryClasses);

        $this->travel($clock, 600);
        $transport->process();
        $transport->queue()->assertCount(0);
    }

    /**
     * Queries at the top level read the highest-level partitions. A change in
     * an old partition of a UUID-partitioned summary must propagate up to
     * them after the refresh agent runs.
     */
    public function testUuidPartitionModificationInOldPartitionReachesTopLevel(): void
    {
        $clock = self::mockTime();
        $this->clearRefreshState();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $countries = $entityManager
            ->getRepository(Country::class)
            ->findBy([], ['id' => 'ASC'], 2);

        $country1 = $countries[0] ?? throw new \RuntimeException('Country not found');
        $country2 = $countries[1] ?? throw new \RuntimeException('Country not found');

        $initialCount = $this->getCustomerCount();
        $initialCount1 = $this->getCustomerCount($country1);
        $initialCount2 = $this->getCustomerCount($country2);

        // a customer in the first lowest-level partition of the level-27
        // partition containing 2020-01-01, well before the latest summarized
        // customer. the partition is not the last child of its containing
        // partition

        $milliseconds = intdiv(1577836800000, 1 << 27) * (1 << 27) + 1000;
        $time = \DateTimeImmutable::createFromFormat('U.v', \sprintf('%d.%03d', intdiv($milliseconds, 1000), $milliseconds % 1000))
            ?: throw new \RuntimeException('Invalid time');

        $customer = new IndividualCustomer();
        $customer->setName('Old Customer');
        $customer->setCountry($country1);
        $customer->setAge(30);
        $customer->setGender(Gender::Female);

        (new \ReflectionProperty(Customer::class, 'id'))
            ->setValue($customer, UuidV7::generate($time));

        $partition = $this->getHandlerFactory()
            ->getSummary(CustomerSummary::class)
            ->getPartition()
            ->getLowestPartitionFromEntity($customer);

        $upperPartition = $partition->getContaining();
        $this->assertNotNull($upperPartition);
        $this->assertNotSame($upperPartition->getUpperBound(), $partition->getUpperBound());

        $entityManager->persist($customer);
        $entityManager->flush();
        $entityManager->clear();

        $this->runCustomerSummaryRefreshAgent($clock);

        $this->assertSame($initialCount + 1, $this->getCustomerCount());
        $this->assertSame($initialCount1 + 1, $this->getCustomerCount($country1));
        $this->assertSame($initialCount2, $this->getCustomerCount($country2));

        // later, move the customer to the other country. the upper partitions
        // are past their cooldown period by then

        $this->travel($clock, 1000);

        $customer = $entityManager->find(Customer::class, $customer->getId()->toRfc4122())
            ?? throw new \RuntimeException('Customer not found');

        $customer->setCountry(
            $entityManager->find(Country::class, $country2->getId())
                ?? throw new \RuntimeException('Country not found'),
        );

        $entityManager->flush();
        $entityManager->clear();

        $this->runCustomerSummaryRefreshAgent($clock);

        $this->assertSame($initialCount + 1, $this->getCustomerCount());
        $this->assertSame($initialCount1, $this->getCustomerCount($country1));
        $this->assertSame($initialCount2 + 1, $this->getCustomerCount($country2));
    }
}
