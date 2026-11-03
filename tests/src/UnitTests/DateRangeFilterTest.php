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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Time\Bin\Gregorian\Date;
use Rekalogika\Analytics\Time\Filter\TimeBinDateRangeFilterOptions;
use Rekalogika\Analytics\UX\PanelBundle\Filter\DateRange\DateRangeFilter;

final class DateRangeFilterTest extends TestCase
{
    /**
     * @param array<string,mixed> $input
     * @return DateRangeFilter<\Rekalogika\Analytics\Time\MonotonicTimeBin>
     */
    private function createFilter(array $input): DateRangeFilter
    {
        return new DateRangeFilter(
            options: new TimeBinDateRangeFilterOptions(
                timeBinClass: Date::class,
                label: new TranslatableMessage('Date'),
                help: null,
            ),
            dimension: 'date',
            inputArray: $input,
        );
    }

    public function testValidRange(): void
    {
        $filter = $this->createFilter([
            'start' => '2024-01-05',
            'end' => '2024-02-29',
        ]);

        $this->assertSame('2024-01-05', $filter->getRawStart());
        $this->assertSame('2024-02-29', $filter->getRawEnd());
        $this->assertSame('2024-01-05 - 2024-02-29', $filter->getRawValue());
        $this->assertSame(20240105, $filter->getStart()?->getDatabaseValue());
        $this->assertSame(20240229, $filter->getEnd()?->getDatabaseValue());
        $this->assertInstanceOf(CompositeExpression::class, $filter->createExpression());
    }

    /**
     * @return iterable<string,array{mixed}>
     */
    public static function provideInvalidDate(): iterable
    {
        yield 'free text' => ['foo'];
        yield 'relative' => ['tomorrow'];
        yield 'overflow' => ['2024-02-30'];
        yield 'other format' => ['05/01/2024'];
        yield 'with time' => ['2024-01-05 10:00'];
        yield 'empty' => [''];
        yield 'array' => [['2024-01-05']];
    }

    #[DataProvider('provideInvalidDate')]
    public function testInvalidDateIsEmpty(mixed $invalid): void
    {
        $filter = $this->createFilter([
            'start' => $invalid,
            'end' => '2024-02-29',
        ]);

        $this->assertSame('', $filter->getRawStart());
        $this->assertSame('', $filter->getRawValue());
        $this->assertNull($filter->getStart());
        $this->assertExpression($filter->createExpression(), Comparison::LTE, 20240229);

        $filter = $this->createFilter([
            'start' => '2024-01-05',
            'end' => $invalid,
        ]);

        $this->assertSame('', $filter->getRawEnd());
        $this->assertSame('2024-01-05', $filter->getRawValue());
        $this->assertNull($filter->getEnd());
        $this->assertExpression($filter->createExpression(), Comparison::GTE, 20240105);

        $filter = $this->createFilter([
            'start' => $invalid,
            'end' => $invalid,
        ]);

        $this->assertSame('', $filter->getRawValue());
        $this->assertNull($filter->createExpression());
    }

    public function testStartOnly(): void
    {
        $filter = $this->createFilter(['start' => '2024-01-05']);

        $this->assertSame('2024-01-05', $filter->getRawValue());
        $this->assertExpression($filter->createExpression(), Comparison::GTE, 20240105);
    }

    public function testEndOnly(): void
    {
        $filter = $this->createFilter(['end' => '2024-02-29']);

        $this->assertSame('', $filter->getRawStart());
        $this->assertSame('2024-02-29', $filter->getRawEnd());
        $this->assertExpression($filter->createExpression(), Comparison::LTE, 20240229);
    }

    public function testNoBounds(): void
    {
        $filter = $this->createFilter([]);

        $this->assertSame('', $filter->getRawValue());
        $this->assertNull($filter->createExpression());
    }

    private function assertExpression(
        ?Expression $expression,
        string $operator,
        int $databaseValue,
    ): void {
        $this->assertInstanceOf(Comparison::class, $expression);
        $this->assertSame('date', $expression->getField());
        $this->assertSame($operator, $expression->getOperator());

        $value = $expression->getValue()->getValue();
        $this->assertInstanceOf(Date::class, $value);
        $this->assertSame($databaseValue, $value->getDatabaseValue());
    }
}
