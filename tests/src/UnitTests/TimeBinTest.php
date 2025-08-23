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
use Rekalogika\Analytics\Time\Bin\Gregorian\Date;
use Rekalogika\Analytics\Time\Bin\Gregorian\Hour;
use Rekalogika\Analytics\Time\Bin\Gregorian\Month;
use Rekalogika\Analytics\Time\Bin\Gregorian\Quarter;
use Rekalogika\Analytics\Time\Bin\Gregorian\Year;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekDate;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekYear;
use Rekalogika\Analytics\Time\Bin\MonthBasedWeek\MonthBasedWeekDate;
use Rekalogika\Analytics\Time\Bin\MonthBasedWeek\MonthBasedWeekWeek;
use Rekalogika\Analytics\Time\MonotonicTimeBin;

final class TimeBinTest extends TestCase
{
    /**
     * @param class-string<MonotonicTimeBin> $class
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
     * @param class-string<MonotonicTimeBin> $class
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
     * @param class-string<MonotonicTimeBin> $class
     * @dataProvider timeBinInstanceCachingProvider
     */
    public function testInstanceCaching(
        string $class,
        int $databaseValue,
    ): void {
        // Create two instances with the same arguments
        $instance1 = $class::createFromDatabaseValue($databaseValue);
        $instance2 = $class::createFromDatabaseValue($databaseValue);

        // They should be the exact same instance (identity check)
        $this->assertSame($instance1, $instance2);

        // Test with different timezones
        $timeZone1 = new \DateTimeZone('UTC');
        $timeZone2 = new \DateTimeZone('America/New_York');

        $instanceUTC1 = $instance1->withTimeZone($timeZone1);
        $instanceUTC2 = $instance1->withTimeZone($timeZone1);
        $instanceNY1 = $instance1->withTimeZone($timeZone2);
        $instanceNY2 = $instance1->withTimeZone($timeZone2);

        // Same timezone, same database value - should be same instance
        $this->assertSame($instanceUTC1, $instanceUTC2);
        $this->assertSame($instanceNY1, $instanceNY2);

        // Different timezone - should be different instance
        $this->assertNotSame($instanceUTC1, $instanceNY1);
    }

    /**
     * @return iterable<array-key,array{class:class-string<MonotonicTimeBin>,databaseValue:int}>
     */
    public static function timeBinInstanceCachingProvider(): iterable
    {
        yield ['class' => Year::class, 'databaseValue' => 2023];
        yield ['class' => Quarter::class, 'databaseValue' => 20233];
        yield ['class' => Month::class, 'databaseValue' => 202303];
        yield ['class' => Date::class, 'databaseValue' => 20231001];
        yield ['class' => Hour::class, 'databaseValue' => 2023100112];
        yield ['class' => IsoWeekYear::class, 'databaseValue' => 2023];
        yield ['class' => IsoWeekWeek::class, 'databaseValue' => 202304];
        yield ['class' => IsoWeekDate::class, 'databaseValue' => 2023042];
        yield ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2025071];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250712];
    }

    /**
     * @return iterable<array-key,array{class:class-string<MonotonicTimeBin>,databaseValue:int,expectedStart:string,expectedEnd:string}>
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
            'class' => IsoWeekWeek::class,
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
            'class' => MonthBasedWeekWeek::class,
            'databaseValue' => 2025071,
            'expectedStart' => '2025-06-30 00:00:00',
            'expectedEnd' => '2025-07-07 00:00:00',
        ];

        yield [
            'class' => MonthBasedWeekDate::class,
            'databaseValue' => 20250712,
            'expectedStart' => '2025-07-01 00:00:00',
            'expectedEnd' => '2025-07-02 00:00:00',
        ];
    }

    /**
     * @return iterable<array-key,array{class:class-string<MonotonicTimeBin>,dateTime:\DateTimeImmutable,databaseValue:int}>
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
            'class' => IsoWeekWeek::class,
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
            'class' => MonthBasedWeekWeek::class,
            'dateTime' => new \DateTimeImmutable('2025-06-30 00:00:00'),
            'databaseValue' => 2025071,
        ];

        yield [
            'class' => MonthBasedWeekDate::class,
            'dateTime' => new \DateTimeImmutable('2025-07-01 00:00:00'),
            'databaseValue' => 20250712,
        ];
    }
}
