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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Bundle\Command\TruncateCommand;
use Rekalogika\Analytics\Bundle\EventListener\RefreshCommandOutputEventSubscriber;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentLock;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class TruncateTest extends KernelTestCase
{
    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    private function getSummaryRowCount(): int
    {
        $count = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->createQueryBuilder()
            ->select('COUNT(s.count)')
            ->from(OrderSummary::class, 's')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertIsNumeric($count);

        return (int) $count;
    }

    public function testTruncate(): void
    {
        $summaryHandler = $this->getHandlerFactory()->getSummary(OrderSummary::class);

        $this->assertNotNull($summaryHandler->getLatestKey());
        $this->assertGreaterThan(0, $this->getSummaryRowCount());

        $summaryHandler->truncate();

        $this->assertSame(0, $this->getSummaryRowCount());

        // the cached latest key is cleared too

        $this->assertNull($summaryHandler->getLatestKey());

        $this->assertSame([], static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class]));
    }

    public function testTruncateCommandTakesRefreshLock(): void
    {
        $lock = new class implements RefreshAgentLock {
            /**
             * @var list<string>
             */
            public array $calls = [];

            #[\Override]
            public function acquire(string $summaryClass): bool
            {
                $this->calls[] = 'acquire ' . $summaryClass;

                return true;
            }

            #[\Override]
            public function waitAndAcquire(string $summaryClass): void
            {
                $this->calls[] = 'waitAndAcquire ' . $summaryClass;
            }

            #[\Override]
            public function release(string $summaryClass): void
            {
                $this->calls[] = 'release ' . $summaryClass;
            }
        };

        $outputSubscriber = static::getContainer()
            ->get('rekalogika.analytics.event_subscriber.refresh_command_output');

        $this->assertInstanceOf(RefreshCommandOutputEventSubscriber::class, $outputSubscriber);

        $command = new TruncateCommand(
            refreshCommandOutputEventSubscriber: $outputSubscriber,
            summaryMetadataFactory: static::getContainer()->get(SummaryMetadataFactory::class),
            handlerFactory: $this->getHandlerFactory(),
            refreshAgentLock: $lock,
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);
        $tester->execute(['class' => OrderSummary::class]);
        $tester->assertCommandIsSuccessful();

        $this->assertSame(0, $this->getSummaryRowCount());

        $this->assertSame([
            'waitAndAcquire ' . OrderSummary::class,
            'release ' . OrderSummary::class,
        ], $lock->calls);
    }
}
