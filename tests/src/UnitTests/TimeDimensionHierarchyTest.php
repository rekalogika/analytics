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
use Rekalogika\Analytics\TimeDimensionHierarchy\Date;
use Rekalogika\Analytics\TimeDimensionHierarchy\Hour;
use Rekalogika\Analytics\TimeDimensionHierarchy\Interval;
use Rekalogika\Analytics\TimeDimensionHierarchy\Month;
use Rekalogika\Analytics\TimeDimensionHierarchy\Quarter;
use Rekalogika\Analytics\TimeDimensionHierarchy\Week;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekDate;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Year;

class TimeDimensionHierarchyTest extends TestCase
{
    /**
     * @dataProvider intervalProvider
     * @param class-string<Interval> $class
     */
    public function testInterval(
        int $databaseInput,
        string $class,
        \DateTimeInterface $expectedStart,
        \DateTimeInterface $expectedEnd,
        string $expectedToString,
    ): void {
        $interval = $class::createFromDatabaseValue(
            databaseValue: $databaseInput,
            timeZone: new \DateTimeZone('UTC'),
        );

        $start = $interval->getStart();
        $end = $interval->getEnd();

        $this->assertEquals($expectedStart, $start);
        $this->assertEquals($expectedEnd, $end);
        $this->assertEquals($expectedToString, (string) $interval);
    }

    /**
     * @return iterable<array-key,array{int,class-string<Interval>,\DateTimeInterface,\DateTimeInterface}>
     */
    public static function intervalProvider(): iterable
    {
        yield [
            2022,
            Year::class,
            new \DateTimeImmutable('2022-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            '2022',
        ];

        yield [
            20222,
            Quarter::class,
            new \DateTimeImmutable('2022-04-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-07-01T00:00:00+00:00'),
            '2022 Q2',
        ];

        yield [
            202202,
            Month::class,
            new \DateTimeImmutable('2022-02-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-01T00:00:00+00:00'),
            'February 2022',
        ];

        yield [
            20220201,
            Date::class,
            new \DateTimeImmutable('2022-02-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-02-02T00:00:00+00:00'),
            '2022-02-01',
        ];

        yield [
            2022020103,
            Hour::class,
            new \DateTimeImmutable('2022-02-01T03:00:00+00:00'),
            new \DateTimeImmutable('2022-02-01T04:00:00+00:00'),
            '2022-02-01 03:00',
        ];

        yield [
            2022,
            WeekYear::class,
            new \DateTimeImmutable('2022-01-03T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-02T00:00:00+00:00'),
            '2022',
        ];

        yield [
            202212,
            Week::class,
            new \DateTimeImmutable('2022-03-21T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-28T00:00:00+00:00'),
            '2022-03-21 - 2022-03-27',
        ];

        yield [
            2022123,
            WeekDate::class,
            new \DateTimeImmutable('2022-03-23T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-24T00:00:00+00:00'),
            '2022-W12-3',
        ];


    }
}
