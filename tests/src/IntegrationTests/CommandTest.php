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

use Doctrine\Common\Collections\Criteria;
use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\Serialization\CoordinatesMapper;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\Category;
use Rekalogika\Analytics\Tests\App\Entity\ClassCast\Shipment;
use Rekalogika\Analytics\Tests\App\Entity\Customer;
use Rekalogika\Analytics\Tests\App\Entity\CustomerSummary;
use Rekalogika\Analytics\Tests\App\Entity\FilteredOrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\IndividualCustomer;
use Rekalogika\Analytics\Tests\App\Entity\Item;
use Rekalogika\Analytics\Tests\App\Entity\Order;
use Rekalogika\Analytics\Tests\App\Entity\OrderExplicitGroupingSetsSummary;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CommandTest extends KernelTestCase
{
    /**
     * @return iterable<string,array{string,string}>
     */
    public static function provideCommandsWithOptionalClassArgument(): iterable
    {
        yield 'refresh' => ['rekalogika:analytics:refresh', 'class'];
        yield 'truncate' => ['rekalogika:analytics:truncate', 'class'];
        yield 'debug:summary' => ['rekalogika:analytics:debug:summary', 'summaryClass'];
        yield 'debug:cell' => ['rekalogika:analytics:debug:cell', 'summaryClass'];
    }

    /**
     * @return iterable<string,array{string,string}>
     */
    public static function provideCommandsWithClassArgument(): iterable
    {
        yield 'refresh' => ['rekalogika:analytics:refresh', 'class'];
        yield 'refresh:range' => ['rekalogika:analytics:refresh:range', 'class'];
        yield 'truncate' => ['rekalogika:analytics:truncate', 'class'];
        yield 'debug:summary' => ['rekalogika:analytics:debug:summary', 'summaryClass'];
        yield 'debug:source' => ['rekalogika:analytics:debug:source', 'sourceClass'];
        yield 'debug:cell' => ['rekalogika:analytics:debug:cell', 'summaryClass'];
        yield 'verify' => ['rekalogika:analytics:verify', 'class'];
    }

    /**
     * Without the class argument the command would ask for it, which is not
     * possible in non-interactive mode.
     */
    #[DataProvider('provideCommandsWithOptionalClassArgument')]
    public function testMissingClassInNonInteractiveMode(
        string $commandName,
        string $argumentName,
    ): void {
        $tester = $this->createCommandTester($commandName);
        $exitCode = $tester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString(
            \sprintf('The "%s" argument is required in non-interactive mode.', $argumentName),
            $this->getDisplay($tester),
        );
    }

    #[DataProvider('provideCommandsWithClassArgument')]
    public function testNonexistentClass(
        string $commandName,
        string $argumentName,
    ): void {
        $tester = $this->createCommandTester($commandName);
        $exitCode = $tester->execute(
            [$argumentName => 'App\Nonexistent'],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString(
            'Class "App\Nonexistent"',
            $this->getDisplay($tester),
        );
    }

    public function testDebugSummaryWithExistingClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:summary');
        $exitCode = $tester->execute(
            ['summaryClass' => OrderSummary::class],
            ['interactive' => false],
        );

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(
            OrderSummary::class,
            $this->getDisplay($tester),
        );
    }

    public function testDebugSourceWithExistingClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:source');
        $exitCode = $tester->execute(
            ['sourceClass' => Order::class],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Source Class ' . Order::class, $display);
        $this->assertStringContainsString(' time ' . FilteredOrderSummary::class . ' ' . OrderExplicitGroupingSetsSummary::class, $display);

        // the change set keys that are not mapped are not shown

        $this->assertStringNotContainsString(' item.category ' . OrderExplicitGroupingSetsSummary::class, $display);

        // the paths through an association are tracked, nothing reads Order
        // through an association

        $this->assertStringNotContainsString('Untracked Related Paths', $display);
        $this->assertStringNotContainsString('Read Through Associations', $display);
    }

    public function testDebugSourceOfRelatedClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:source');
        $exitCode = $tester->execute(
            ['sourceClass' => Item::class],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringNotContainsString('Tracked Properties', $display);
        $this->assertStringContainsString('Read Through Associations', $display);
        $this->assertStringContainsString(' category ' . Order::class . ' item ' . OrderExplicitGroupingSetsSummary::class, $display);
        $this->assertStringContainsString(' countryOfOrigin ' . Order::class . ' item ' . OrderSummary::class, $display);
        $this->assertStringContainsString(' (deletion) ' . Order::class . ' item ', $display);
    }

    public function testDebugSourceOfSubclass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:source');
        $exitCode = $tester->execute(
            ['sourceClass' => IndividualCustomer::class],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(' gender ' . CustomerSummary::class, $display);
        $this->assertStringContainsString(' country ' . CustomerSummary::class, $display);
    }

    public function testDebugSourceOfClassWithoutSummary(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:source');
        $exitCode = $tester->execute(
            ['sourceClass' => Shipment::class],
            ['interactive' => false],
        );

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No summary depends on this class.', $this->getDisplay($tester));
    }

    public function testDebugSourceWithoutClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:source');
        $exitCode = $tester->execute([], ['interactive' => false]);

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Source Class ' . Order::class, $display);
        $this->assertStringContainsString('Source Class ' . Customer::class, $display);
        $this->assertStringContainsString('Source Class ' . Item::class, $display);
        $this->assertStringContainsString('Source Class ' . Category::class, $display);
    }

    public function testDebugCell(): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        $coordinatesMapper = self::getContainer()->get(CoordinatesMapper::class);
        $this->assertInstanceOf(CoordinatesMapper::class, $coordinatesMapper);

        $cell = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->dice(Criteria::expr()->in('time.civil.year', [2023, 2024]))
            ->getResult()
            ->drillDown('customerCountry')
            ->first();

        $this->assertNotNull($cell);

        $coordinates = json_encode(
            $coordinatesMapper->toDto($cell),
            JSON_THROW_ON_ERROR,
        );

        $tester = $this->createCommandTester('rekalogika:analytics:debug:cell');
        $exitCode = $tester->execute(
            [
                'summaryClass' => OrderSummary::class,
                'coordinates' => $coordinates,
            ],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(' customerCountry Customer country ', $display);
        $this->assertStringContainsString(' count Count ', $display);
        $this->assertStringContainsString('Valid As Of', $display);
        $this->assertStringContainsString('time.civil.year IN (2023, 2024)', $display);
        $this->assertStringContainsString($coordinates, $display);
        $this->assertStringContainsString('FROM "order"', $display);
        $this->assertStringContainsString('FROM order_summary', $display);
    }

    public function testDebugApexCell(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:cell');
        $exitCode = $tester->execute(
            ['summaryClass' => OrderSummary::class],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('None, this is the apex cell.', $display);
        $this->assertStringContainsString('FROM order_summary', $display);
    }

    /**
     * A cell missing from the result of the summary table has no summary
     * query, but the source query is still shown.
     */
    public function testDebugCellMissingFromResult(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:cell');
        $exitCode = $tester->execute(
            [
                'summaryClass' => OrderSummary::class,
                'coordinates' => '{"class":"coordinates","members":{"time.civil.month.month":"190001"},"predicate":null}',
            ],
            ['interactive' => false],
        );

        $display = $this->getDisplay($tester);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('FROM "order"', $display);
        $this->assertStringContainsString('None, the cell is not in the result of the summary table.', $display);
    }

    public function testDebugCellWithInvalidCoordinates(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:cell');
        $exitCode = $tester->execute(
            [
                'summaryClass' => OrderSummary::class,
                'coordinates' => '{"members":',
            ],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Invalid coordinates:', $this->getDisplay($tester));
    }

    public function testDebugCellOfNonSummaryClass(): void
    {
        $tester = $this->createCommandTester('rekalogika:analytics:debug:cell');
        $exitCode = $tester->execute(
            ['summaryClass' => Order::class],
            ['interactive' => false],
        );

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('is not a summary class.', $this->getDisplay($tester));
    }

    private function createCommandTester(string $commandName): CommandTester
    {
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find($commandName));
    }

    /**
     * Collapses the line wrapping and padding added by SymfonyStyle.
     */
    private function getDisplay(CommandTester $tester): string
    {
        return (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
    }
}
