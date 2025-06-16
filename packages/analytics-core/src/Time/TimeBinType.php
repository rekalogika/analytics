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

namespace Rekalogika\Analytics\Time;

use Doctrine\DBAL\Types\Types;

enum TimeBinType: string
{
    case Hour = 'hour';
    case HourOfDay = 'hourOfDay';

    case Date = 'date';
    case DayOfWeek = 'dayOfWeek';
    case DayOfMonth = 'dayOfMonth';
    case DayOfYear = 'dayOfYear';

    case Week = 'week';
    case WeekDate = 'weekDate';
    case WeekYear = 'weekYear';
    case WeekOfYear = 'weekOfYear';
    case WeekOfMonth = 'weekOfMonth';

    case Month = 'month';
    case MonthOfYear = 'monthOfYear';

    case Quarter = 'quarter';
    case QuarterOfYear = 'quarterOfYear';

    case Year = 'year';

    /**
     * This is mainly for documentation purposes.
     */
    public function getDoctrineType(): string
    {
        return match ($this) {
            // HourTrait
            self::Hour => 'rekalogika_analytics_hour',
            self::HourOfDay => Types::SMALLINT,

            // DayTrait
            self::Date => 'rekalogika_analytics_date',
            self::WeekDate => 'rekalogika_analytics_week_date',
            self::DayOfWeek => Types::SMALLINT,
            self::DayOfMonth => Types::SMALLINT,
            self::DayOfYear => Types::SMALLINT,

            // MonthTrait
            self::Month => 'rekalogika_analytics_month',
            self::MonthOfYear => Types::SMALLINT,

            // QuarterTrait
            self::Quarter => 'rekalogika_analytics_quarter',
            self::QuarterOfYear => Types::SMALLINT,

            // WeekTrait
            self::Week => 'rekalogika_analytics_week',
            self::WeekOfYear => Types::SMALLINT,
            self::WeekOfMonth => Types::SMALLINT,

            // WeekYearTrait
            self::WeekYear => 'rekalogika_analytics_week_year',

            // YearTrait
            self::Year => 'rekalogika_analytics_year',
        };
    }

    /**
     * @return class-string
     */
    public function getBinClass(): string
    {
        return match ($this) {
            // HourTrait
            self::Hour => Bin\Hour::class,
            self::HourOfDay => Bin\HourOfDay::class,

            // DayTrait
            self::Date => Bin\Date::class,
            self::WeekDate => Bin\WeekDate::class,
            self::DayOfWeek => Bin\DayOfWeek::class,
            self::DayOfMonth => Bin\DayOfMonth::class,
            self::DayOfYear => Bin\DayOfYear::class,

            // MonthTrait
            self::Month => Bin\Month::class,
            self::MonthOfYear => Bin\MonthOfYear::class,

            // QuarterTrait
            self::Quarter => Bin\Quarter::class,
            self::QuarterOfYear => Bin\QuarterOfYear::class,

            // WeekTrait
            self::Week => Bin\Week::class,
            self::WeekOfYear => Bin\WeekOfYear::class,
            self::WeekOfMonth => Bin\WeekOfMonth::class,

            // WeekYearTrait
            self::WeekYear => Bin\WeekYear::class,

            // YearTrait
            self::Year => Bin\Year::class,
        };
    }
}
