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
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Bin\Hour;
use Rekalogika\Analytics\Time\Bin\IsoWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeekDate;
use Rekalogika\Analytics\Time\Bin\IsoWeekYear;
use Rekalogika\Analytics\Time\Bin\Month;
use Rekalogika\Analytics\Time\Bin\MonthWeek;
use Rekalogika\Analytics\Time\Bin\MonthWeekDate;
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\Year;
use Rekalogika\Analytics\Time\TimeBin;

final class TimeBinTest extends TestCase
{
    /**
     * @param class-string<TimeBin> $class
     * @dataProvider timeBinProvider
     */
    public function testDatabaseValueToObject(
        string $class,
        int $databaseValue,
        string $expectedStart,
        string $expectedEnd,
    ): void {
        $timeBin = $class::createFromDatabaseValue($databaseValue);

        $start = $timeBin->getStart()->format('Y-m-d H:i:s');
        $end = $timeBin->getEnd()->format('Y-m-d H:i:s');

        $this->assertSame($expectedStart, $start);
        $this->assertSame($expectedEnd, $end);
    }

    /**
     * @param class-string<TimeBin> $class
     * @dataProvider timeBinDateTimeProvider
     */
    public function testDateTimeToObject(
        string $class,
        \DateTimeInterface $dateTime,
        int $databaseValue,
    ): void {
        $timeBin = $class::createFromDateTime($dateTime);
        $this->assertInstanceOf($class, $timeBin);
        $this->assertSame($databaseValue, $timeBin->getDatabaseValue());
    }

    /**
     * @return iterable<array-key,array{class:class-string<TimeBin>,databaseValue:int,expectedStart:string,expectedEnd:string}>
     */
    public static function timeBinProvider(): iterable
    {
        yield [
            'class' => Year::class,
            'databaseValue' => 2023,
            'expectedStart' => '2023-01-01 00:00:00',
            'expectedEnd' => '2024-01-01 00:00:00',
        ];

        yield [
            'class' => Quarter::class,
            'databaseValue' => 20233,
            'expectedStart' => '2023-07-01 00:00:00',
            'expectedEnd' => '2023-10-01 00:00:00',
        ];

        yield [
            'class' => Month::class,
            'databaseValue' => 202303,
            'expectedStart' => '2023-03-01 00:00:00',
            'expectedEnd' => '2023-04-01 00:00:00',
        ];

        yield [
            'class' => Date::class,
            'databaseValue' => 20231001,
            'expectedStart' => '2023-10-01 00:00:00',
            'expectedEnd' => '2023-10-02 00:00:00',
        ];

        yield [
            'class' => Hour::class,
            'databaseValue' => 2023100112,
            'expectedStart' => '2023-10-01 12:00:00',
            'expectedEnd' => '2023-10-01 13:00:00',
        ];

        // iso week

        yield [
            'class' => IsoWeekYear::class,
            'databaseValue' => 2023,
            'expectedStart' => '2023-01-02 00:00:00',
            'expectedEnd' => '2024-01-01 00:00:00',
        ];

        yield [
            'class' => IsoWeek::class,
            'databaseValue' => 202304,
            'expectedStart' => '2023-01-23 00:00:00',
            'expectedEnd' => '2023-01-30 00:00:00',
        ];

        yield [
            'class' => IsoWeekDate::class,
            'databaseValue' => 2023042,
            'expectedStart' => '2023-01-24 00:00:00',
            'expectedEnd' => '2023-01-25 00:00:00',
        ];

        // month based week

        yield [
            'class' => MonthWeek::class,
            'databaseValue' => 2025071,
            'expectedStart' => '2025-06-30 00:00:00',
            'expectedEnd' => '2025-07-07 00:00:00',
        ];

        yield [
            'class' => MonthWeekDate::class,
            'databaseValue' => 20250712,
            'expectedStart' => '2025-07-01 00:00:00',
            'expectedEnd' => '2025-07-02 00:00:00',
        ];
    }

    /**
     * @return iterable<array-key,array{class:class-string<TimeBin>,dateTime:\DateTimeImmutable,databaseValue:int}>
     */
    public static function timeBinDateTimeProvider(): iterable
    {
        yield [
            'class' => Year::class,
            'dateTime' => new \DateTimeImmutable('2023-01-01 00:00:00'),
            'databaseValue' => 2023,
        ];

        yield [
            'class' => Quarter::class,
            'dateTime' => new \DateTimeImmutable('2023-07-01 00:00:00'),
            'databaseValue' => 20233,
        ];

        yield [
            'class' => Month::class,
            'dateTime' => new \DateTimeImmutable('2023-03-01 00:00:00'),
            'databaseValue' => 202303,
        ];

        yield [
            'class' => Date::class,
            'dateTime' => new \DateTimeImmutable('2023-10-01 00:00:00'),
            'databaseValue' => 20231001,
        ];

        yield [
            'class' => Hour::class,
            'dateTime' => new \DateTimeImmutable('2023-10-01 12:00:00'),
            'databaseValue' => 2023100112,
        ];

        // iso week

        yield [
            'class' => IsoWeekYear::class,
            'dateTime' => new \DateTimeImmutable('2023-01-02 00:00:00'),
            'databaseValue' => 2023,
        ];

        yield [
            'class' => IsoWeek::class,
            'dateTime' => new \DateTimeImmutable('2023-01-23 00:00:00'),
            'databaseValue' => 202304,
        ];

        yield [
            'class' => IsoWeekDate::class,
            'dateTime' => new \DateTimeImmutable('2023-01-24 00:00:00'),
            'databaseValue' => 2023042,
        ];

        // month based week

        yield [
            'class' => MonthWeek::class,
            'dateTime' => new \DateTimeImmutable('2025-06-30 00:00:00'),
            'databaseValue' => 2025071,
        ];

        yield [
            'class' => MonthWeekDate::class,
            'dateTime' => new \DateTimeImmutable('2025-07-01 00:00:00'),
            'databaseValue' => 20250712,
        ];
    }
}
