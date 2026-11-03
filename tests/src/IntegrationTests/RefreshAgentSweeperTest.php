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
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentSweepCommand;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentSweeper;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class RefreshAgentSweeperTest extends KernelTestCase
{
    use InteractsWithMessenger;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM rekalogika_summary_dirty');
        $connection->executeStatement('DELETE FROM rekalogika_summary_refresh_log');
        $connection->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');
    }

    public function testNothingToSweep(): void
    {
        $this->assertSame([], $this->getSweeper()->sweep());
        $this->transport('async')->queue()->assertEmpty();
    }

    /**
     * A flag written without a flush, e.g. by a database trigger, is never
     * dispatched otherwise.
     */
    public function testFlagWithoutScheduledRun(): void
    {
        $this->insertFlag(OrderSummary::class);

        $this->assertSame([OrderSummary::class], $this->getSweeper()->sweep());

        $queue = $this->transport('async')->queue();
        $queue->assertCount(1);
        $queue->assertContains(RefreshAgentStartCommand::class);

        $message = $queue->first(RefreshAgentStartCommand::class)->getMessage();
        $this->assertInstanceOf(RefreshAgentStartCommand::class, $message);
        $this->assertSame(OrderSummary::class, $message->getSummaryClass());

        // the run is now scheduled, a second sweep does nothing

        $this->assertSame([], $this->getSweeper()->sweep());
        $this->transport('async')->queue()->assertCount(1);
    }

    /**
     * The message of an overdue run may still be in the queue. Within the
     * grace period, it is not dispatched again.
     */
    public function testOverdueRunWithinGracePeriod(): void
    {
        $this->insertFlag(OrderSummary::class);
        $this->setScheduledRefresh(OrderSummary::class, '-200 seconds');

        $this->assertSame([], $this->getSweeper()->sweep());
        $this->transport('async')->queue()->assertEmpty();
    }

    public function testOverdueRunAfterGracePeriod(): void
    {
        $this->insertFlag(OrderSummary::class);
        $this->insertFlag(CustomerSummary::class);
        $this->setScheduledRefresh(OrderSummary::class, '-400 seconds');
        $this->setScheduledRefresh(CustomerSummary::class, '+60 seconds');

        $this->assertSame([OrderSummary::class], $this->getSweeper()->sweep());
        $this->transport('async')->queue()->assertCount(1);
    }

    public function testSweepMessage(): void
    {
        $this->insertFlag(OrderSummary::class);

        static::getContainer()
            ->get(MessageBusInterface::class)
            ->dispatch(new RefreshAgentSweepCommand());

        $this->transport('async')->queue()->assertCount(1);
        $this->transport('async')->queue()->assertContains(RefreshAgentStartCommand::class);
    }

    public function testSweepCommand(): void
    {
        $this->insertFlag(OrderSummary::class);

        $tester = $this->createCommandTester('rekalogika:analytics:sweep');
        $exitCode = $tester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(OrderSummary::class, $tester->getDisplay());
        $this->transport('async')->queue()->assertCount(1);

        $tester->execute([], ['interactive' => false]);
        $this->assertStringContainsString('Nothing to dispatch.', $tester->getDisplay());
    }

    public function testStatusCommand(): void
    {
        $this->insertFlag(OrderSummary::class);
        $this->insertFlag(OrderSummary::class);
        $this->setScheduledRefresh(OrderSummary::class, '-400 seconds');

        $tester = $this->createCommandTester('rekalogika:analytics:status');
        $exitCode = $tester->execute(
            ['class' => OrderSummary::class],
            ['interactive' => false],
        );

        $display = (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
        $level = $this->getLowestLevel(OrderSummary::class);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(OrderSummary::class, $display);
        $this->assertStringContainsString(\sprintf('Dirty flags level %d: 2', $level), $display);
        $this->assertStringContainsString('(overdue)', $display);
        $this->assertStringContainsString('Latest summarized key', $display);
    }

    public function testStatusCommandWithoutClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:status');
        $exitCode = $tester->execute([], ['interactive' => false]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(OrderSummary::class, $tester->getDisplay());
        $this->assertStringContainsString(CustomerSummary::class, $tester->getDisplay());
    }

    public function testStatusCommandWithNonSummaryClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:status');
        $exitCode = $tester->execute(
            ['class' => \stdClass::class],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('is not a summary class', $tester->getDisplay());
    }

    private function getSweeper(): RefreshAgentSweeper
    {
        $sweeper = static::getContainer()->get('rekalogika.analytics.refresh.sweeper');
        $this->assertInstanceOf(RefreshAgentSweeper::class, $sweeper);

        return $sweeper;
    }

    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    /**
     * @param class-string $summaryClass
     */
    private function getLowestLevel(string $summaryClass): int
    {
        return $this->getHandlerFactory()
            ->getSummary($summaryClass)
            ->getPartition()
            ->createLowestPartitionFromSourceValue(1)
            ->getLevel();
    }

    /**
     * Inserts a lowest-level dirty flag without dispatching the refresh
     * agent, like a database trigger would.
     *
     * @param class-string $summaryClass
     */
    private function insertFlag(string $summaryClass): void
    {
        $summaryHandler = $this->getHandlerFactory()->getSummary($summaryClass);

        $partition = $summaryHandler
            ->getPartition()
            ->createLowestPartitionFromSourceValue(
                $summaryHandler->getSource()->getLatestKey()
                    ?? throw new \RuntimeException('No source records'),
            );

        $dirtyFlags = $summaryHandler->getDirtyFlags();
        $dirtyFlags->insertDirtyFlag($dirtyFlags->createDirtyFlagForPartition($partition));
    }

    /**
     * @param class-string $summaryClass
     */
    private function setScheduledRefresh(string $summaryClass, string $modifier): void
    {
        $this->getHandlerFactory()
            ->getSummary($summaryClass)
            ->updateScheduledRefresh(new \DateTimeImmutable($modifier));
    }

    private function createCommandTester(string $commandName): CommandTester
    {
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find($commandName));
    }
}
