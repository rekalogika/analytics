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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Time\Bin\Gregorian\Date;
use Rekalogika\Analytics\Time\Bin\Gregorian\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\Gregorian\Hour;
use Rekalogika\Analytics\Time\Bin\Gregorian\Month;
use Rekalogika\Analytics\Time\Bin\Gregorian\MonthOfYear;
use Rekalogika\Analytics\Time\Bin\Gregorian\Quarter;
use Rekalogika\Analytics\Time\Bin\Gregorian\Year;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekDate;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekYear;
use Rekalogika\Analytics\Time\Bin\MonthBasedWeek\MonthBasedWeekDate;
use Rekalogika\Analytics\Time\Bin\MonthBasedWeek\MonthBasedWeekWeek;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatableInterface;

final class TimeBinTest extends TestCase
{
    /**
     * @param class-string<MonotonicTimeBin> $class
     */
    #[DataProvider('timeBinProvider')]
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
     */
    #[DataProvider('timeBinDateTimeProvider')]
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
     */
    #[DataProvider('timeBinInstanceCachingProvider')]
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

    public function testHourSequenceAcrossSpringForward(): void
    {
        // 2025-03-09 in New York: the clock jumps from 02:00 EST to 03:00 EDT,
        // so hour 02 does not exist.
        $timeZone = new \DateTimeZone('America/New_York');
        $hour = Hour::createFromDatabaseValue(2025030903)->withTimeZone($timeZone);

        $this->assertSame('2025-03-09 03:00:00 EDT', $hour->getStart()->format('Y-m-d H:i:s T'));
        $this->assertSame('2025-03-09 04:00:00 EDT', $hour->getEnd()->format('Y-m-d H:i:s T'));

        $previous = $hour->getPrevious();
        $this->assertNotSame($hour, $previous);
        $this->assertSame(2025030901, $previous->getDatabaseValue());
        $this->assertSame('2025-03-09 01:00:00 EST', $previous->getStart()->format('Y-m-d H:i:s T'));
        $this->assertSame('2025-03-09 03:00:00 EDT', $previous->getEnd()->format('Y-m-d H:i:s T'));

        $this->assertSame(2025030903, $previous->getNext()->getDatabaseValue());
        $this->assertSame(2025030904, $hour->getNext()->getDatabaseValue());
    }

    public function testHourInSpringForwardGapIsEmpty(): void
    {
        $timeZone = new \DateTimeZone('America/New_York');
        $hour = Hour::createFromDatabaseValue(2025030902)->withTimeZone($timeZone);

        $this->assertEquals($hour->getStart(), $hour->getEnd());
        $this->assertSame(2025030901, $hour->getPrevious()->getDatabaseValue());
        $this->assertSame(2025030903, $hour->getNext()->getDatabaseValue());
    }

    public function testHourSequenceAcrossFallBack(): void
    {
        // 2025-11-02 in New York: the clock goes from 02:00 EDT back to 01:00
        // EST, so hour 01 occurs twice and the bin spans two hours.
        $timeZone = new \DateTimeZone('America/New_York');
        $hour = Hour::createFromDatabaseValue(2025110201)->withTimeZone($timeZone);

        $this->assertSame('2025-11-02 01:00:00 EDT', $hour->getStart()->format('Y-m-d H:i:s T'));
        $this->assertSame('2025-11-02 02:00:00 EST', $hour->getEnd()->format('Y-m-d H:i:s T'));

        $this->assertSame(2025110200, $hour->getPrevious()->getDatabaseValue());
        $this->assertSame(2025110202, $hour->getNext()->getDatabaseValue());
        $this->assertSame(2025110201, $hour->getNext()->getPrevious()->getDatabaseValue());
        $this->assertSame(2025110201, $hour->getPrevious()->getNext()->getDatabaseValue());
    }

    public function testHourDescendingWalkAcrossSpringForwardTerminates(): void
    {
        $timeZone = new \DateTimeZone('Europe/Berlin');
        // 2025-03-30: 02:00 CET -> 03:00 CEST
        $current = Hour::createFromDatabaseValue(2025033005)->withTimeZone($timeZone);
        $last = Hour::createFromDatabaseValue(2025033000)->withTimeZone($timeZone);

        $visited = [];

        while ($current !== $last) {
            $visited[] = $current->getDatabaseValue();
            $current = $current->getPrevious();
            $this->assertLessThan(10, \count($visited), 'Descending walk did not terminate.');
        }

        $visited[] = $current->getDatabaseValue();

        $this->assertSame(
            [2025033005, 2025033004, 2025033003, 2025033001, 2025033000],
            $visited,
        );
    }

    public function testMonthLabelUsesCalendarYear(): void
    {
        // 2021-01-01 is a Friday, so it belongs to ISO week-year 2020.
        $month = Month::createFromDatabaseValue(202101);

        foreach (['de_DE' => 'Januar 2021', 'fr_FR' => 'janvier 2021', 'en_GB' => 'January 2021'] as $locale => $expected) {
            $translator = new IdentityTranslator();
            $translator->setLocale($locale);

            $this->assertSame($expected, $month->trans($translator));
        }

        $week = MonthBasedWeekWeek::createFromDatabaseValue(2021011);

        $this->assertSame(
            'Week 1, Januar 2021',
            $week->trans(new IdentityTranslator(), 'de_DE'),
        );
    }

    public function testMonthBasedWeekLabelInAnotherTimeZone(): void
    {
        $defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $week = MonthBasedWeekWeek::createFromDatabaseValue(2025021)
                ->withTimeZone(new \DateTimeZone('America/New_York'));

            $this->assertSame(
                'Week 1, February 2025',
                $week->trans(new IdentityTranslator(), 'en'),
            );
        } finally {
            date_default_timezone_set($defaultTimeZone);
        }
    }

    public function testWeekTitles(): void
    {
        $translator = new IdentityTranslator();

        $this->assertSame(
            'Week starting on 30 December 2024, ending on 5 January 2025',
            IsoWeekWeek::createFromDatabaseValue(202501)->getTitle()->trans($translator, 'en'),
        );

        $this->assertSame(
            'Week starting on 3 February 2025, ending on 9 February 2025',
            MonthBasedWeekWeek::createFromDatabaseValue(2025021)->getTitle()->trans($translator, 'en'),
        );
    }

    public function testMonthBasedWeekDateSequence(): void
    {
        $date = MonthBasedWeekDate::createFromDatabaseValue(20250712);

        $this->assertSame('2025-07-W1-2', (string) $date);
        $this->assertSame('2025-07-W1-2', $date->trans(new IdentityTranslator()));
        $this->assertSame(20250713, $date->getNext()->getDatabaseValue());
        $this->assertSame(20250711, $date->getPrevious()->getDatabaseValue());

        // 2025-06-30 is the Monday of the first week of July, 2025-06-29 the
        // Sunday of the fourth week of June.
        $this->assertSame(
            20250647,
            MonthBasedWeekDate::createFromDatabaseValue(20250711)->getPrevious()->getDatabaseValue(),
        );

        // walk two months day by day
        $current = MonthBasedWeekDate::createFromDatabaseValue(20250111);

        for ($i = 0; $i < 60; $i++) {
            $next = $current->getNext();

            $this->assertEquals(
                \DateTimeImmutable::createFromInterface($current->getStart())->modify('+1 day'),
                $next->getStart(),
            );
            $this->assertSame($current, $next->getPrevious());

            $current = $next;
        }
    }

    /**
     * @param array{de_DE:string,fr_FR:string} $expected
     */
    #[DataProvider('localeProvider')]
    public function testTransLocale(TranslatableInterface $timeBin, array $expected): void
    {
        $translator = new IdentityTranslator();
        $translator->setLocale('de_DE');

        // the translator's locale when no locale is given
        $this->assertSame($expected['de_DE'], $timeBin->trans($translator));

        // the given locale otherwise
        $this->assertSame($expected['fr_FR'], $timeBin->trans($translator, 'fr_FR'));
    }

    /**
     * @return iterable<array-key,array{timeBin:TranslatableInterface,expected:array{de_DE:string,fr_FR:string}}>
     */
    public static function localeProvider(): iterable
    {
        yield 'month' => [
            'timeBin' => Month::createFromDatabaseValue(202503),
            'expected' => ['de_DE' => 'März 2025', 'fr_FR' => 'mars 2025'],
        ];

        yield 'day of week' => [
            'timeBin' => DayOfWeek::Monday,
            'expected' => ['de_DE' => 'Montag', 'fr_FR' => 'lundi'],
        ];

        yield 'month of year' => [
            'timeBin' => MonthOfYear::March,
            'expected' => ['de_DE' => 'März', 'fr_FR' => 'mars'],
        ];

        yield 'date' => [
            'timeBin' => Date::createFromDatabaseValue(20250303),
            'expected' => ['de_DE' => '03.03.2025', 'fr_FR' => '3 mars 2025'],
        ];

        yield 'iso week' => [
            'timeBin' => IsoWeekWeek::createFromDatabaseValue(202510),
            'expected' => ['de_DE' => '03.03.2025 - 09.03.2025', 'fr_FR' => '3 mars 2025 - 9 mars 2025'],
        ];

        yield 'month based week' => [
            'timeBin' => MonthBasedWeekWeek::createFromDatabaseValue(2025031),
            'expected' => ['de_DE' => 'Week 1, März 2025', 'fr_FR' => 'Week 1, mars 2025'],
        ];
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

        // Friday to Sunday belong to the week of the preceding Thursday

        yield [
            'class' => MonthBasedWeekWeek::class,
            'dateTime' => new \DateTimeImmutable('2025-01-31 12:00:00'),
            'databaseValue' => 2025015,
        ];

        yield [
            'class' => MonthBasedWeekWeek::class,
            'dateTime' => new \DateTimeImmutable('2025-02-02 23:00:00'),
            'databaseValue' => 2025015,
        ];

        yield [
            'class' => MonthBasedWeekWeek::class,
            'dateTime' => new \DateTimeImmutable('2025-02-03 00:00:00'),
            'databaseValue' => 2025021,
        ];

        yield [
            'class' => MonthBasedWeekDate::class,
            'dateTime' => new \DateTimeImmutable('2025-01-31 12:00:00'),
            'databaseValue' => 20250155,
        ];

        yield [
            'class' => MonthBasedWeekDate::class,
            'dateTime' => new \DateTimeImmutable('2025-02-02 00:00:00'),
            'databaseValue' => 20250157,
        ];
    }
}
