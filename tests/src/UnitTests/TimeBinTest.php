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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
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
use Rekalogika\Analytics\Time\Filter\TimeBinNumberRangeFilterOptions;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Rekalogika\Analytics\Time\TimeBin;
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

    public function testInstanceCacheReleasesUnusedInstances(): void
    {
        $cache = new \ReflectionProperty(Year::class, 'instances');
        $countBefore = \count((array) $cache->getValue());

        $instance = Year::createFromDatabaseValue(2987);
        $reference = \WeakReference::create($instance);

        $this->assertCount($countBefore + 1, (array) $cache->getValue());

        unset($instance);

        $this->assertNull($reference->get());
        $this->assertCount($countBefore, (array) $cache->getValue());
    }

    public function testCreateFromDatabaseValueAndTimeZone(): void
    {
        $cache = new \ReflectionProperty(Year::class, 'instances');
        $countBefore = \count((array) $cache->getValue());
        $timeZone = new \DateTimeZone('Asia/Jakarta');

        $instance = Year::createFromDatabaseValueAndTimeZone(2986, $timeZone);

        // no intermediate UTC instance
        $this->assertCount($countBefore + 1, (array) $cache->getValue());

        $this->assertSame(
            Year::createFromDatabaseValue(2986)->withTimeZone($timeZone),
            $instance,
        );

        $this->assertSame('Asia/Jakarta', $instance->getStart()->getTimezone()->getName());
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

    /**
     * @param class-string<MonotonicTimeBin> $class
     * @param non-empty-string $timeZone
     */
    #[DataProvider('contiguousBinProvider')]
    public function testBinsAreContiguousAcrossDstTransitions(
        string $class,
        string $timeZone,
    ): void {
        $timeZone = new \DateTimeZone($timeZone);
        $bin = $class::createFromDateTime(new \DateTimeImmutable('2000-01-03 12:00:00', $timeZone));
        $last = new \DateTimeImmutable('2030-01-01 00:00:00', $timeZone);
        $failures = [];

        // collect the failures instead of asserting each bin, which is slow
        while ($bin->getStart() < $last) {
            $next = $bin->getNext();

            $start = $bin->getStart()->format('c');

            if ($next === null) {
                $this->fail(\sprintf('The bin starting at %s has no next bin', $start));
            }

            if ($bin->getStart() >= $bin->getEnd()) {
                $failures[] = \sprintf('The bin starting at %s is empty', $start);
            }

            if ($bin->getEnd() != $next->getStart()) {
                $failures[] = \sprintf(
                    'The bin starting at %s ends at %s, the next one starts at %s',
                    $start,
                    $bin->getEnd()->format('c'),
                    $next->getStart()->format('c'),
                );
            }

            $bin = $next;
        }

        $this->assertSame([], $failures);
    }

    /**
     * @return iterable<string,array{class:class-string<MonotonicTimeBin>,timeZone:non-empty-string}>
     */
    public static function contiguousBinProvider(): iterable
    {
        $classes = [
            Date::class,
            Month::class,
            Quarter::class,
            Year::class,
            IsoWeekDate::class,
            IsoWeekWeek::class,
            IsoWeekYear::class,
            MonthBasedWeekDate::class,
            MonthBasedWeekWeek::class,
        ];

        // time zones where the clock has changed at midnight
        $timeZones = [
            'America/Santiago',
            'America/Asuncion',
            'America/Havana',
            'Asia/Beirut',
            'Africa/Cairo',
            'America/Sao_Paulo',
            'Asia/Tehran',
        ];

        foreach ($classes as $class) {
            foreach ($timeZones as $timeZone) {
                $shortName = (new \ReflectionClass($class))->getShortName();

                yield \sprintf('%s, %s', $shortName, $timeZone) => [
                    'class' => $class,
                    'timeZone' => $timeZone,
                ];
            }
        }
    }

    public function testDayBinsAgreeOnAmbiguousMidnight(): void
    {
        // 2000-10-29 in Havana: the clock goes from 01:00 CDT back to 00:00
        // CST, so midnight occurs twice. The day starts at the first one.
        $timeZone = new \DateTimeZone('America/Havana');
        $dateTime = new \DateTimeImmutable('2000-10-29 12:00:00', $timeZone);
        $expected = '2000-10-29 00:00:00 CDT';

        $this->assertSame($expected, Date::createFromDateTime($dateTime)->getStart()->format('Y-m-d H:i:s T'));
        $this->assertSame($expected, IsoWeekDate::createFromDateTime($dateTime)->getStart()->format('Y-m-d H:i:s T'));
        $this->assertSame($expected, MonthBasedWeekDate::createFromDateTime($dateTime)->getStart()->format('Y-m-d H:i:s T'));

        $this->assertSame(
            $expected,
            Date::createFromDateTime($dateTime->modify('-1 day'))->getEnd()->format('Y-m-d H:i:s T'),
        );
    }

    public function testDayBinInSpringForwardGapAtMidnight(): void
    {
        // 2022-03-22 in Tehran: the clock jumps from 00:00 to 01:00
        $timeZone = new \DateTimeZone('Asia/Tehran');
        $date = Date::createFromDatabaseValue(20220322)->withTimeZone($timeZone);

        $this->assertSame('2022-03-22 01:00:00 +0430', $date->getStart()->format('Y-m-d H:i:s O'));
        $this->assertSame('2022-03-23 00:00:00 +0430', $date->getEnd()->format('Y-m-d H:i:s O'));
        $this->assertSame('2022-03-21 00:00:00 +0330', $date->getPrevious()->getStart()->format('Y-m-d H:i:s O'));
        $this->assertSame('2022-03-22 01:00:00 +0430', $date->getPrevious()->getEnd()->format('Y-m-d H:i:s O'));
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
        $translator->setLocale('de_DE');

        $isoWeek = IsoWeekWeek::createFromDatabaseValue(202501);
        $monthBasedWeek = MonthBasedWeekWeek::createFromDatabaseValue(2025021);

        // the dates follow the given locale (the message itself is not
        // translated by the identity translator)
        $this->assertSame(
            'Week starting on 30 December 2024, ending on 5 January 2025',
            $isoWeek->getTitle()->trans($translator, 'en_GB'),
        );

        $this->assertSame(
            'Week starting on 3 February 2025, ending on 9 February 2025',
            $monthBasedWeek->getTitle()->trans($translator, 'en_GB'),
        );

        // or the translator's locale if none is given
        $this->assertSame(
            'Week starting on 30. Dezember 2024, ending on 5. Januar 2025',
            $isoWeek->getTitle()->trans($translator),
        );

        $this->assertSame(
            'Week starting on 3. Februar 2025, ending on 9. Februar 2025',
            $monthBasedWeek->getTitle()->trans($translator),
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

    public function testIsoWeekOneStartingInPreviousYear(): void
    {
        // 2025-W01 starts on Monday, 2024-12-30
        $week = IsoWeekWeek::createFromDatabaseValue(202501);

        $this->assertSame('2025-W01', (string) $week);
        $this->assertSame('2024-12-30 00:00:00', $week->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2025-01-06 00:00:00', $week->getEnd()->format('Y-m-d H:i:s'));
        $this->assertSame(202452, $week->getPrevious()->getDatabaseValue());

        $year = IsoWeekYear::createFromDatabaseValue(2025);

        $this->assertSame('2024-12-30 00:00:00', $year->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2025-12-29 00:00:00', $year->getEnd()->format('Y-m-d H:i:s'));

        $date = IsoWeekDate::createFromDatabaseValue(2025011);

        $this->assertSame('2025-W01-1', (string) $date);
        $this->assertSame('2024-12-30 00:00:00', $date->getStart()->format('Y-m-d H:i:s'));
        $this->assertSame(2024527, $date->getPrevious()->getDatabaseValue());

        // the last day of the calendar year 2024 is in the ISO week-year 2025,
        // the day before it in 2024

        $lastDay = new \DateTimeImmutable('2024-12-31 12:00:00');

        $this->assertSame(202501, IsoWeekWeek::createFromDateTime($lastDay)->getDatabaseValue());
        $this->assertSame(2025, IsoWeekYear::createFromDateTime($lastDay)->getDatabaseValue());
        $this->assertSame(2025012, IsoWeekDate::createFromDateTime($lastDay)->getDatabaseValue());

        $sunday = new \DateTimeImmutable('2024-12-29 23:00:00');

        $this->assertSame(202452, IsoWeekWeek::createFromDateTime($sunday)->getDatabaseValue());
        $this->assertSame(2024, IsoWeekYear::createFromDateTime($sunday)->getDatabaseValue());
        $this->assertSame(2024527, IsoWeekDate::createFromDateTime($sunday)->getDatabaseValue());
    }

    /**
     * @param class-string<MonotonicTimeBin> $class
     */
    #[DataProvider('nextDatabaseValueProvider')]
    public function testNextDatabaseValue(
        string $class,
        int $databaseValue,
        int $expectedNext,
    ): void {
        $timeBin = $class::createFromDatabaseValue($databaseValue);
        $next = $timeBin->getNext();

        $this->assertInstanceOf($class, $next);
        $this->assertSame($expectedNext, $next->getDatabaseValue());
        $this->assertSame($next, $class::createFromDatabaseValue($expectedNext));
        $this->assertEquals($timeBin->getEnd(), $next->getStart());
        $this->assertSame($timeBin, $next->getPrevious());
    }

    /**
     * @return iterable<string,array{class:class-string<MonotonicTimeBin>,databaseValue:int,expectedNext:int}>
     */
    public static function nextDatabaseValueProvider(): iterable
    {
        yield 'year' => ['class' => Year::class, 'databaseValue' => 2024, 'expectedNext' => 2025];
        yield 'quarter' => ['class' => Quarter::class, 'databaseValue' => 20232, 'expectedNext' => 20233];
        yield 'quarter, end of year' => ['class' => Quarter::class, 'databaseValue' => 20234, 'expectedNext' => 20241];
        yield 'month' => ['class' => Month::class, 'databaseValue' => 202402, 'expectedNext' => 202403];
        yield 'month, end of year' => ['class' => Month::class, 'databaseValue' => 202312, 'expectedNext' => 202401];
        yield 'date, leap day' => ['class' => Date::class, 'databaseValue' => 20240228, 'expectedNext' => 20240229];
        yield 'date, end of february' => ['class' => Date::class, 'databaseValue' => 20230228, 'expectedNext' => 20230301];
        yield 'date, end of year' => ['class' => Date::class, 'databaseValue' => 20241231, 'expectedNext' => 20250101];
        yield 'hour' => ['class' => Hour::class, 'databaseValue' => 2024061512, 'expectedNext' => 2024061513];
        yield 'hour, end of year' => ['class' => Hour::class, 'databaseValue' => 2023123123, 'expectedNext' => 2024010100];
        yield 'iso week year' => ['class' => IsoWeekYear::class, 'databaseValue' => 2020, 'expectedNext' => 2021];
        yield 'iso week' => ['class' => IsoWeekWeek::class, 'databaseValue' => 202510, 'expectedNext' => 202511];
        yield 'iso week, week 52 to week 53' => ['class' => IsoWeekWeek::class, 'databaseValue' => 202052, 'expectedNext' => 202053];
        yield 'iso week, week 53 to week 1' => ['class' => IsoWeekWeek::class, 'databaseValue' => 202053, 'expectedNext' => 202101];
        yield 'iso week, week 52 to week 1' => ['class' => IsoWeekWeek::class, 'databaseValue' => 202452, 'expectedNext' => 202501];
        yield 'iso week date' => ['class' => IsoWeekDate::class, 'databaseValue' => 2025011, 'expectedNext' => 2025012];
        yield 'iso week date, end of week' => ['class' => IsoWeekDate::class, 'databaseValue' => 2025017, 'expectedNext' => 2025021];
        yield 'iso week date, end of 53-week year' => ['class' => IsoWeekDate::class, 'databaseValue' => 2020537, 'expectedNext' => 2021011];
        yield 'iso week date, end of 52-week year' => ['class' => IsoWeekDate::class, 'databaseValue' => 2024527, 'expectedNext' => 2025011];
        yield 'month based week' => ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2025071, 'expectedNext' => 2025072];
        yield 'month based week, end of month' => ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2025015, 'expectedNext' => 2025021];
        yield 'month based week, end of year' => ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2024124, 'expectedNext' => 2025011];
        yield 'month based week date' => ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250712, 'expectedNext' => 20250713];
        yield 'month based week date, end of week' => ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250717, 'expectedNext' => 20250721];
        yield 'month based week date, end of month' => ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250157, 'expectedNext' => 20250211];
        yield 'month based week date, end of year' => ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20241247, 'expectedNext' => 20250111];
    }

    /**
     * @param class-string<TimeBin> $class
     */
    #[DataProvider('invalidDatabaseValueProvider')]
    public function testInvalidDatabaseValue(string $class, int $databaseValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $class::createFromDatabaseValue($databaseValue);
    }

    /**
     * @return iterable<array-key,array{class:class-string<TimeBin>,databaseValue:int}>
     */
    public static function invalidDatabaseValueProvider(): iterable
    {
        yield ['class' => DayOfWeek::class, 'databaseValue' => 8];
        yield ['class' => MonthOfYear::class, 'databaseValue' => 0];
        yield ['class' => Date::class, 'databaseValue' => 20241399];
        yield ['class' => Date::class, 'databaseValue' => 20240230];
        yield ['class' => Hour::class, 'databaseValue' => 2024010125];
        yield ['class' => IsoWeekWeek::class, 'databaseValue' => 202153];
        yield ['class' => IsoWeekWeek::class, 'databaseValue' => 202500];
        yield ['class' => Month::class, 'databaseValue' => 202413];
        yield ['class' => Month::class, 'databaseValue' => 202400];
        yield ['class' => IsoWeekDate::class, 'databaseValue' => 2021531];
        yield ['class' => IsoWeekDate::class, 'databaseValue' => 2025010];
        yield ['class' => IsoWeekDate::class, 'databaseValue' => 2025018];
        yield ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2025025];
        yield ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2025020];
        yield ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 2024131];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250251];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250210];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20250218];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 20241311];

        // extra digits
        yield ['class' => Year::class, 'databaseValue' => 20261];
        yield ['class' => Quarter::class, 'databaseValue' => 202613];
        yield ['class' => Quarter::class, 'databaseValue' => 20260];
        yield ['class' => Month::class, 'databaseValue' => 2026011];
        yield ['class' => Date::class, 'databaseValue' => 202601011];
        yield ['class' => Hour::class, 'databaseValue' => 20260101011];
        yield ['class' => MonthBasedWeekWeek::class, 'databaseValue' => 20250711];
        yield ['class' => MonthBasedWeekDate::class, 'databaseValue' => 202507121];
        yield ['class' => IsoWeekWeek::class, 'databaseValue' => 2025101];
        yield ['class' => IsoWeekDate::class, 'databaseValue' => 20251011];
    }

    public function testNumberRangeFilterKeepsInvalidNumber(): void
    {
        $options = new TimeBinNumberRangeFilterOptions(
            timeBinClass: Quarter::class,
            label: new TranslatableMessage('Quarter'),
            help: null,
        );

        $this->assertSame(20261, $options->transformInputToDatabaseValue(20261));

        // a mistyped bin is compared as is and matches nothing
        $this->assertSame(202613, $options->transformInputToDatabaseValue(202613));
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
