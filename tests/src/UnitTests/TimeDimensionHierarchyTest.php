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
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Translator;

final class TimeDimensionHierarchyTest extends TestCase
{
    /**
     * @dataProvider intervalProvider
     * @param class-string<MonotonicTimeBin> $class
     */
    public function testInterval(
        int $databaseInput,
        string $class,
        \DateTimeInterface $expectedStart,
        \DateTimeInterface $expectedEnd,
        string $expectedToString,
        string $expectedTranslated,
    ): void {
        $messageFormatter = new MessageFormatter();
        $translator = new Translator('en', $messageFormatter);

        $interval = $class::createFromDatabaseValue($databaseInput);

        $start = $interval->getStart();
        $end = $interval->getEnd();

        $this->assertEquals($expectedStart, $start);
        $this->assertEquals($expectedEnd, $end);
        $this->assertEquals($expectedToString, (string) $interval);
        $this->assertEquals($expectedTranslated, $interval->trans($translator, 'en'));
    }

    /**
     * @return iterable<array-key,array{int,class-string<MonotonicTimeBin>,\DateTimeInterface,\DateTimeInterface,string,string}>
     */
    public static function intervalProvider(): iterable
    {
        yield [
            2022,
            Year::class,
            new \DateTimeImmutable('2022-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            '2022',
            '2022',
        ];

        yield [
            20222,
            Quarter::class,
            new \DateTimeImmutable('2022-04-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-07-01T00:00:00+00:00'),
            '2022-Q2',
            '2022 Q2',
        ];

        yield [
            202202,
            Month::class,
            new \DateTimeImmutable('2022-02-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-01T00:00:00+00:00'),
            '2022-02',
            'February 2022',
        ];

        yield [
            20220201,
            Date::class,
            new \DateTimeImmutable('2022-02-01T00:00:00+00:00'),
            new \DateTimeImmutable('2022-02-02T00:00:00+00:00'),
            '2022-02-01',
            'Feb 1, 2022',
        ];

        yield [
            2022020103,
            Hour::class,
            new \DateTimeImmutable('2022-02-01T03:00:00+00:00'),
            new \DateTimeImmutable('2022-02-01T04:00:00+00:00'),
            '2022-02-01 03:00',
            '2022-02-01 03:00',
        ];

        yield [
            2022,
            IsoWeekYear::class,
            new \DateTimeImmutable('2022-01-03T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-02T00:00:00+00:00'),
            '2022',
            '2022',
        ];

        yield [
            202212,
            IsoWeekWeek::class,
            new \DateTimeImmutable('2022-03-21T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-28T00:00:00+00:00'),
            '2022-W12',
            'Mar 21, 2022 - Mar 27, 2022',
        ];

        yield [
            2022123,
            IsoWeekDate::class,
            new \DateTimeImmutable('2022-03-23T00:00:00+00:00'),
            new \DateTimeImmutable('2022-03-24T00:00:00+00:00'),
            '2022-W12-3',
            '2022-W12-3',
        ];


    }
}
