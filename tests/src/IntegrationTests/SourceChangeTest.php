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

        // clear existing dirty flags if exists

        $entityManager
            ->getRepository(DirtyFlag::class)
            ->createQueryBuilder('df')
            ->delete()
            ->getQuery()
            ->execute();

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

        // check messenger in 0.999999 seconds, should not consume the message

        $clock->sleep(0.999999);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 1st second it should process the message.

        $clock->sleep(0.000001);
        $transport->process();
        $transport->queue()->assertCount(0);

        // the count should be updated now

        $this->assertEquals($initialCount + 1, $this->getOrderCount());

        // dump($transport->queue()->messages());
    }

    public function testSourceModification(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // clear existing dirty flags if exists

        $entityManager
            ->getRepository(DirtyFlag::class)
            ->createQueryBuilder('df')
            ->delete()
            ->getQuery()
            ->execute();

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

        // check messenger in 0.999999 seconds, should not consume the message

        $clock->sleep(0.999999);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 1st second it should process the message.

        $clock->sleep(0.000001);
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

        // clear existing dirty flags if exists

        $entityManager
            ->getRepository(DirtyFlag::class)
            ->createQueryBuilder('df')
            ->delete()
            ->getQuery()
            ->execute();

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

        // check messenger in 0.999999 seconds, should not consume the message

        $clock->sleep(0.999999);
        $transport->process();
        $transport->queue()->assertCount(2);

        // in the exact 1st second it should process the message.

        $clock->sleep(0.000001);
        $transport->process();
        $transport->queue()->assertCount(0);

        // we check the result

        $this->assertEquals($initialCount - 1, $this->getOrderCount());
    }
}
