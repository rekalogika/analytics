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
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\SummaryRefresher\Event\NewDirtyFlagEvent;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\EventListener\TestNewDirtyFlagListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
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

        // check dirty flag

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]);

        $this->assertCount(1, $dirtyFlags);
        $dirtyFlag = $dirtyFlags[0];
        $this->assertEquals(OrderSummary::class, $dirtyFlag->getClass());
        $this->assertNull($dirtyFlag->getKey());
        $this->assertNull($dirtyFlag->getLevel());

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
}
