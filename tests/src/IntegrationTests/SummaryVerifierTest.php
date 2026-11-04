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
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\SummaryVerifier\SummaryVerifier;
use Rekalogika\Analytics\Engine\SummaryVerifier\SummaryVerifierFactory;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\FilteredOrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\OccupancyHistorySummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SummaryVerifierTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getConnection()->executeStatement('DELETE FROM rekalogika_summary_dirty');
    }

    /**
     * The order summaries are large, only a part of them is verified.
     *
     * @return iterable<string,array{class-string,int|null,int|null}>
     */
    public static function provideSummaryClasses(): iterable
    {
        yield 'order' => [OrderSummary::class, 40, 47];
        yield 'order, explicit grouping sets' => [OrderExplicitGroupingSetsSummary::class, 40, 47];
        yield 'filtered order' => [FilteredOrderSummary::class, null, null];
        yield 'customer, uuid partition' => [CustomerSummary::class, null, null];
        yield 'occupancy history' => [OccupancyHistorySummary::class, null, null];
    }

    /**
     * @param class-string $summaryClass
     */
    #[DataProvider('provideSummaryClasses')]
    public function testFreshSummaryMatches(string $summaryClass, ?int $start, ?int $end): void
    {
        $result = $this->createVerifier($summaryClass)->verifyRange($start, $end);

        $this->assertSame([], $result->getMismatched());
        $this->assertGreaterThan(0, $result->getVerifiedCount());
        $this->assertTrue($result->isValid());
    }

    public function testUndetectedChange(): void
    {
        $this->changeOrderTimeBehindTheOrm(50);

        $result = $this->createVerifier(OrderSummary::class)->verifyRange(48, 51);
        $mismatched = $result->getMismatched();

        $this->assertCount(1, $mismatched);
        $this->assertSame($this->getLowestPartitionKey(OrderSummary::class, 50), $mismatched[0]->getKey());
        $this->assertFalse($result->isValid());
    }

    public function testRange(): void
    {
        $this->changeOrderTimeBehindTheOrm(50);

        $verifier = $this->createVerifier(OrderSummary::class);

        $this->assertCount(1, $verifier->verifyRange(48, 51)->getMismatched());
        $this->assertCount(0, $verifier->verifyRange(100, 107)->getMismatched());
    }

    public function testDirtyPartitionIsSkipped(): void
    {
        $this->changeOrderTimeBehindTheOrm(50);

        $this->getSummaryManager()->markDirty(OrderSummary::class, [50]);

        $result = $this->createVerifier(OrderSummary::class)->verifyRange(48, 51);

        $this->assertSame([], $result->getMismatched());

        $skippedKeys = array_map(
            static fn($partition): int => $partition->getKey(),
            $result->getSkipped(),
        );

        $this->assertContains($this->getLowestPartitionKey(OrderSummary::class, 50), $skippedKeys);
    }

    public function testSample(): void
    {
        $result = $this->createVerifier(FilteredOrderSummary::class)->verifySample(3);

        $this->assertSame([], $result->getMismatched());
        $this->assertGreaterThan(0, $result->getVerifiedCount());
        $this->assertLessThanOrEqual(3, $result->getVerifiedCount());
    }

    public function testCommand(): void
    {
        $tester = $this->createCommandTester();
        $exitCode = $tester->execute(['class' => FilteredOrderSummary::class]);

        $this->assertSame(Command::SUCCESS, $exitCode, $this->getDisplay($tester));
        $this->assertStringContainsString('The summary matches its source.', $this->getDisplay($tester));
    }

    public function testCommandWithDifference(): void
    {
        $this->changeOrderTimeBehindTheOrm(50);

        $tester = $this->createCommandTester();
        $exitCode = $tester->execute([
            'class' => OrderSummary::class,
            'start' => '48',
            'end' => '51',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('differs from its source in 1 partitions', $this->getDisplay($tester));
        $this->assertSame(0, $this->getDirtyFlagCount());
    }

    public function testCommandRepair(): void
    {
        $this->changeOrderTimeBehindTheOrm(50);

        $tester = $this->createCommandTester();
        $exitCode = $tester->execute([
            'class' => OrderSummary::class,
            'start' => '48',
            'end' => '51',
            '--repair' => true,
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('marked dirty', $this->getDisplay($tester));

        $this->assertSame(
            [$this->getLowestPartitionKey(OrderSummary::class, 50)],
            array_map(
                static fn(mixed $key): int => \is_int($key) || \is_string($key) ? (int) $key : 0,
                $this->getConnection()->fetchFirstColumn(
                    'SELECT DISTINCT key FROM rekalogika_summary_dirty WHERE class = ? AND key IS NOT NULL',
                    [OrderSummary::class],
                ),
            ),
        );
    }

    public function testCommandSample(): void
    {
        $tester = $this->createCommandTester();
        $exitCode = $tester->execute([
            'class' => FilteredOrderSummary::class,
            '--sample' => '2',
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode, $this->getDisplay($tester));
    }

    public function testCommandSampleWithRange(): void
    {
        $tester = $this->createCommandTester();
        $exitCode = $tester->execute([
            'class' => OrderSummary::class,
            'start' => '1',
            '--sample' => '2',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('A sample cannot be combined with a range.', $this->getDisplay($tester));
    }

    /**
     * Moves the order to 2030 with native SQL, which is not detected.
     */
    private function changeOrderTimeBehindTheOrm(int $id): void
    {
        $this->getConnection()->executeStatement(
            "UPDATE \"order\" SET time = '2030-02-01 00:00:00' WHERE id = ?",
            [$id],
        );
    }

    /**
     * @param class-string $summaryClass
     */
    private function getLowestPartitionKey(string $summaryClass, int $sourceKey): int
    {
        return $this->getHandlerFactory()
            ->getSummary($summaryClass)
            ->getPartition()
            ->createLowestPartitionFromSourceValue($sourceKey)
            ->getKey();
    }

    /**
     * @param class-string $summaryClass
     */
    private function createVerifier(string $summaryClass): SummaryVerifier
    {
        $factory = static::getContainer()->get('rekalogika.analytics.engine.summary_verifier_factory');
        $this->assertInstanceOf(SummaryVerifierFactory::class, $factory);

        return $factory->createSummaryVerifier($summaryClass);
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find('rekalogika:analytics:verify'));
    }

    /**
     * Collapses the line wrapping and padding added by SymfonyStyle.
     */
    private function getDisplay(CommandTester $tester): string
    {
        return (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
    }

    private function getHandlerFactory(): HandlerFactory
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory;
    }

    private function getSummaryManager(): SummaryManager
    {
        return static::getContainer()->get(SummaryManager::class);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function getDirtyFlagCount(): int
    {
        $count = $this->getConnection()->fetchOne('SELECT COUNT(*) FROM rekalogika_summary_dirty');
        $this->assertTrue(\is_int($count) || \is_string($count));

        return (int) $count;
    }
}
