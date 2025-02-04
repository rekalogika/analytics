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
use Rekalogika\Analytics\Model\Entity\DirtyFlag;
use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\Query\SummaryItem;
use Rekalogika\Analytics\SummaryManagerRegistry;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\EventListener\TestNewDirtyFlagListener;
use Rekalogika\Analytics\TimeDimensionHierarchy\Year;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class SourceChangeTest extends KernelTestCase
{
    use InteractsWithMessenger;
    use ClockSensitiveTrait;

    /**
     * Sequences are not affected by transactions, so we need to reset them
     * manually
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $connection = static::getContainer()
            ->get(Connection::class);

        $sql = "SELECT setval(pg_get_serial_sequence('order', 'id'), coalesce(max(id), 1), false) FROM \"order\"";

        $connection->executeStatement($sql);
    }

    private function getOrderCount(): int
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManagerRegistry::class)
            ->getManager(OrderSummary::class);

        $result = $summaryManager->createQuery()
            ->select('count')
            ->getResult();

        $child = $result->getChildren()[0]
            ?? throw new \RuntimeException('No children found');
        $this->assertInstanceOf(LeafNode::class, $child);
        $count = $child->getValue();
        $this->assertIsInt($count);

        return $count;
    }

    private function getOrderCountIn2030(): int
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManagerRegistry::class)
            ->getManager(OrderSummary::class);

        $result = $summaryManager->createQuery()
            ->groupBy('time.year')
            ->select('count')
            ->getResult();

        $children = $result->getChildren();

        $subItem = null;

        foreach ($children as $c) {
            $item = $c->getItem();
            $this->assertInstanceOf(Year::class, $item);

            if ((string) $item === '2030') {
                $subItem = $c;
                break;
            }
        }

        if (!$subItem instanceof SummaryItem) {
            return 0;
        }

        $child = $subItem->getChildren()[0]
            ?? throw new \RuntimeException('No children found');
        $this->assertInstanceOf(LeafNode::class, $child);
        $count = $child->getValue();
        $this->assertIsInt($count);

        return $count;
    }

    public function testSourceCreation(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // get the current result

        $this->assertEquals(200, $this->getOrderCount());

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

        // persist and flush

        $entityManager->persist($order);
        $entityManager->flush();
        $entityManager->clear();

        // check dirty flag

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findAll();

        $this->assertCount(1, $dirtyFlags);
        $dirtyFlag = $dirtyFlags[0];
        $this->assertEquals(OrderSummary::class, $dirtyFlag->getClass());
        $this->assertNull($dirtyFlag->getKey());
        $this->assertNull($dirtyFlag->getLevel());

        // check if NewDirtyFlagEvent is emitted

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $this->assertCount(1, $listener->getEvents());

        // check messenger now, it should have one pending message

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(1);

        // check messenger in 59 seconds, should not consume the message

        $clock->sleep(59);
        $transport->process();
        $transport->queue()->assertCount(1);

        // in the exact 60th second it should process the message. the
        // processing converts the "there are new entities" refresh command to
        // the "refresh this partition" command. the message should generate two
        // refreshCommands:
        //
        // 1. primary "refresh this partition" command, delayed 60s
        // 2. secondary "there are new entities" command, delayed 300s

        $clock->sleep(1);
        $transport->process(1);
        $transport->queue()->assertCount(2);

        // cannot process the messages now as they are delayed

        $transport->process();
        $transport->queue()->assertCount(2);

        // the first message will be processed after 60 seconds, and the
        // then the corresponding secondary refresh command will be dispatched

        $clock->sleep(60);
        $transport->process();
        $transport->queue()->assertCount(2);

        // the count should be updated now

        $this->assertEquals(201, $this->getOrderCount());


        // the second message will be processed after 240 seconds (300 - 60)

        $clock->sleep(240);
        $transport->process();
        $transport->queue()->assertCount(1);

        // the other secondary command will be processed after 60 seconds
        // or 300 seconds after the primary was processed

        $clock->sleep(60);
        $transport->process();
        $transport->queue()->assertCount(0);

        // dump($transport->queue()->messages());
    }

    public function testSourceModification(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // get the current result

        $this->assertEquals(0, $this->getOrderCountIn2030());

        // get one order and modify the time to 2030

        $order = $entityManager
            ->getRepository(Order::class)
            ->findOneBy([])
            ?? throw new \RuntimeException('Order not found');

        $order->setTime(new \DateTimeImmutable('2030-02-01 00:00:00'));

        // flush

        $entityManager->flush();
        $entityManager->clear();

        // check dirty flag

        $dirtyFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findAll();

        $this->assertCount(1, $dirtyFlags);
        $dirtyFlag = $dirtyFlags[0];
        $this->assertEquals(OrderSummary::class, $dirtyFlag->getClass());
        $this->assertNotNull($dirtyFlag->getKey());
        $this->assertNotNull($dirtyFlag->getLevel());

        // check if NewDirtyFlagEvent is emitted

        $listener = static::getContainer()->get(TestNewDirtyFlagListener::class);
        $this->assertCount(1, $listener->getEvents());

        // check messenger now, it should have one pending message

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(1);

        // check messenger in 59 seconds, should not consume the message

        $clock->sleep(59);
        $transport->process();
        $transport->queue()->assertCount(1);

        // in the exact 60th second it should process the message, and generates
        // the corresponding secondary refresh command

        $clock->sleep(1);

        // i'm lazy so we just blindly process the messages here until it does
        // not emit more messages.

        while ($transport->queue()->messages()) {
            $clock->sleep(60);
            $transport->process();
        }

        // we check the result

        $this->assertEquals(1, $this->getOrderCountIn2030());
    }
}
