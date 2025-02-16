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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Model\Partition\DefaultIntegerPartition;
use Rekalogika\Analytics\RefreshWorker\RefreshScheduler;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\Fixtures\Refresh\Lock;
use Rekalogika\Analytics\Tests\Fixtures\Refresh\TestIntegrationAdapter;
use Rekalogika\Analytics\Tests\Fixtures\Refresh\TestRefreshClassPropertiesResolver;
use Rekalogika\Analytics\Tests\Fixtures\Refresh\TestRefreshRunner;
use Symfony\Component\Clock\MockClock;

/** @psalm-suppress MissingConstructor */
final class RefreshTest extends TestCase
{
    private TestIntegrationAdapter $adapter;

    /**
     * @var RefreshScheduler<Lock>
     */
    private RefreshScheduler $scheduler;

    private TestRefreshRunner $runner;

    private TestRefreshClassPropertiesResolver $propertiesResolver;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('1970-01-01 00:00:00');

        $this->runner = new TestRefreshRunner(
            clock: $this->clock,
            processingTime: 60,
        );

        $this->adapter = new TestIntegrationAdapter($this->clock);

        $this->propertiesResolver = new TestRefreshClassPropertiesResolver(
            class: OrderSummary::class,
            startDelay: 60,
            interval: 600,
            expectedMaximumProcessingTime: 300,
        );

        $this->scheduler = new RefreshScheduler(
            adapter: $this->adapter,
            runner: $this->runner,
            propertiesResolver: $this->propertiesResolver,
        );

        $this->adapter->setScheduler($this->scheduler);
    }

    public function setParameters(
        ?int $startDelay = null,
        ?int $interval = null,
        ?int $expectedMaximumProcessingTime = null,
        ?int $processingTime = null,
    ): void {
        if ($startDelay !== null) {
            $this->propertiesResolver->setStartDelay($startDelay);
        }

        if ($interval !== null) {
            $this->propertiesResolver->setInterval($interval);
        }

        if ($expectedMaximumProcessingTime !== null) {
            $this->propertiesResolver->setExpectedMaximumProcessingTime($expectedMaximumProcessingTime);
        }

        if ($processingTime !== null) {
            $this->runner->setProcessingTime($processingTime);
        }
    }

    private function scheduleWorkerRun(): void
    {
        $this->scheduler->scheduleWorker(
            class: OrderSummary::class,
            partition: DefaultIntegerPartition::createFromSourceValue(0, 55),
        );
    }

    private function assertRunStarted(int $runNumber, string $timeString): void
    {
        $this->assertEquals($timeString, $this->runner->getRunStarts()[$runNumber] ?? null);
    }

    private function assertRunNotStarted(int $runNumber): void
    {
        $this->assertNull($this->runner->getRunStarts()[$runNumber] ?? null);
    }

    private function assertRunFinished(int $runNumber, string $timeString): void
    {
        $this->assertEquals($timeString, $this->runner->getRunFinishes()[$runNumber] ?? null);
    }

    private function timeAdvance(int $seconds): void
    {
        $this->clock->sleep($seconds);
        $this->adapter->wakeUp();
    }

    public function testNoContention(): void
    {
        $this->setParameters(
            startDelay: 60,
            interval: 600,
            expectedMaximumProcessingTime: 300,
            processingTime: 60,
        );

        $this->clock->modify('2025-01-01 00:00:00');
        $this->scheduleWorkerRun();

        $this->timeAdvance(30);
        $this->assertRunNotStarted(0);

        $this->timeAdvance(40);
        $this->assertRunStarted(0, '2025-01-01 00:01:10');
        $this->assertRunFinished(0, '2025-01-01 00:02:10');
    }

    public function testTwoAfterAnother(): void
    {
        $this->setParameters(
            startDelay: 60,
            interval: 600,
            expectedMaximumProcessingTime: 300,
            processingTime: 60,
        );

        $this->clock->modify('2025-01-01 00:00:00');
        $this->scheduleWorkerRun();
        $this->timeAdvance(60);

        $this->assertRunStarted(0, '2025-01-01 00:01:00');

        $this->timeAdvance(700);
        $this->scheduleWorkerRun();
        $this->timeAdvance(60);

        $this->assertRunStarted(1, '2025-01-01 00:14:40');
    }

    public function testTwoConcurrent(): void
    {
        $this->setParameters(
            startDelay: 60,
            interval: 600,
            expectedMaximumProcessingTime: 300,
            processingTime: 60,
        );

        $this->clock->modify('2025-01-01 00:00:00');
        $this->scheduleWorkerRun();
        $this->timeAdvance(60);

        $this->assertRunStarted(0, '2025-01-01 00:01:00');

        $this->timeAdvance(300);
        $this->scheduleWorkerRun();
        $this->assertRunNotStarted(1);

        $this->timeAdvance(300);
        $this->scheduleWorkerRun();
        $this->assertRunStarted(1, '2025-01-01 00:12:00');
    }

    public function testThreeConcurrent(): void
    {
        $this->setParameters(
            startDelay: 60,
            interval: 600,
            expectedMaximumProcessingTime: 300,
            processingTime: 60,
        );

        $this->clock->modify('2025-01-01 00:00:00');
        $this->scheduleWorkerRun();
        $this->timeAdvance(60);

        $this->assertRunStarted(0, '2025-01-01 00:01:00');

        $this->timeAdvance(300);
        $this->scheduleWorkerRun();
        $this->assertRunNotStarted(1);

        $this->timeAdvance(100);
        $this->scheduleWorkerRun();
        $this->assertRunNotStarted(1);
        $this->assertRunNotStarted(2);

        $this->timeAdvance(200);
        $this->assertRunStarted(1, '2025-01-01 00:12:00');
        $this->assertRunNotStarted(2);

        $this->timeAdvance(10000);
        $this->assertRunStarted(1, '2025-01-01 00:12:00');
        $this->assertRunNotStarted(2);
    }
}
