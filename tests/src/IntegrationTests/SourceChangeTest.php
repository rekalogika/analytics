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
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\EventListener\TestNewDirtyFlagListener;
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

    public function testSourceCreation(): void
    {
        $clock = self::mockTime();

        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

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

        // check messenger now

        $transport = $this->transport('async');
        $transport->process();
        $transport->queue()->assertCount(1);

        // check messenger in 59 seconds, should not generate any command

        $clock->sleep(59);
        $transport->process();
        $transport->queue()->assertCount(1);

        // in the exact 60th second.
        // processing the message should generate two refreshCommands:
        // 1. primary refresh command for the new partition
        // 2. secondary refresh command for the new summary

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

}
