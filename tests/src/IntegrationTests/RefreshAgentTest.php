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
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgent;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentDispatcher;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentLock;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentRunner;
use Rekalogika\Analytics\Engine\RefreshAgent\RefreshAgentStartCommand;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadataFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RefreshAgentTest extends KernelTestCase
{
    /**
     * @var list<\DateTimeInterface>
     */
    private array $dispatches = [];

    /**
     * @var list<string>
     */
    private array $lockCalls = [];

    private function recordDispatch(\DateTimeInterface $runAt): void
    {
        $this->dispatches[] = $runAt;
    }

    private function recordLockCall(string $call): void
    {
        $this->lockCalls[] = $call;
    }

    /**
     * Creates a refresh agent whose refresh fails.
     */
    private function createFailingRefreshAgent(bool $failingDispatcher = false): RefreshAgent
    {
        static::getContainer()
            ->get(Connection::class)
            ->executeStatement('UPDATE rekalogika_summary_properties SET scheduled_refresh = NULL');

        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry
            ->method('getManagerForClass')
            ->willThrowException(new \RuntimeException('refresh failed'));

        $summaryRefresherFactory = new SummaryRefresherFactory(
            handlerFactory: $handlerFactory,
            managerRegistry: $managerRegistry,
            metadataFactory: static::getContainer()->get(SummaryMetadataFactory::class),
        );

        $dispatcher = new class ($this->recordDispatch(...), $failingDispatcher) implements RefreshAgentDispatcher {
            /**
             * @param \Closure(\DateTimeInterface):void $onDispatch
             */
            public function __construct(
                private readonly \Closure $onDispatch,
                private readonly bool $failing,
            ) {}

            #[\Override]
            public function dispatch(
                RefreshAgentStartCommand $command,
                \DateTimeInterface $runAt,
            ): void {
                if ($this->failing) {
                    throw new \LogicException('dispatch failed');
                }

                ($this->onDispatch)($runAt);
            }
        };

        $lock = new class ($this->recordLockCall(...)) implements RefreshAgentLock {
            /**
             * @param \Closure(string):void $onCall
             */
            public function __construct(private readonly \Closure $onCall) {}

            #[\Override]
            public function acquire(string $summaryClass): bool
            {
                ($this->onCall)('acquire');

                return true;
            }

            #[\Override]
            public function waitAndAcquire(string $summaryClass): void
            {
                ($this->onCall)('waitAndAcquire');
            }

            #[\Override]
            public function release(string $summaryClass): void
            {
                ($this->onCall)('release');
            }
        };

        return new RefreshAgent(
            summaryRefresherFactory: $summaryRefresherFactory,
            refreshAgentLock: $lock,
            refreshAgentRunner: new RefreshAgentRunner(
                refreshAgentDispatcher: $dispatcher,
                handlerFactory: $handlerFactory,
            ),
        );
    }

    public function testFailedRunSchedulesAnotherRun(): void
    {
        $refreshAgent = $this->createFailingRefreshAgent();
        $before = new \DateTimeImmutable();

        try {
            $refreshAgent->run(new RefreshAgentStartCommand(OrderSummary::class));
            $this->fail('The run should have failed');
        } catch (\RuntimeException $e) {
            $this->assertSame('refresh failed', $e->getMessage());
        }

        $this->assertCount(1, $this->dispatches);
        $this->assertGreaterThan($before->modify('+1 minute'), $this->dispatches[0]);
        $this->assertSame(['acquire', 'release'], $this->lockCalls);
    }

    public function testFailedSchedulingDoesNotReplaceTheException(): void
    {
        $refreshAgent = $this->createFailingRefreshAgent(failingDispatcher: true);

        try {
            $refreshAgent->run(new RefreshAgentStartCommand(OrderSummary::class));
            $this->fail('The run should have failed');
        } catch (\RuntimeException $e) {
            $this->assertSame('refresh failed', $e->getMessage());
        }

        $this->assertSame(['acquire', 'release'], $this->lockCalls);
    }
}
