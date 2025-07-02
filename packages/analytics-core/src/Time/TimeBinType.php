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

enum TimeBinType: string
{
    //
    // enum cases
    //

    // Hour
    case Hour = 'hour';
    case HourOfDay = 'hourOfDay';

    // Date
    case Date = 'date';
    case DayOfWeek = 'dayOfWeek';
    case DayOfMonth = 'dayOfMonth';
    case DayOfYear = 'dayOfYear';
    case IsoDayOfWeekYear = 'isoDayOfWeekYear';
    case IsoWeekDate = 'isoWeekDate';

    // Week
    case IsoWeek = 'isoWeek';
    case IsoWeekOfYear = 'isoWeekOfYear';
    case WeekOfMonth = 'weekOfMonth';
    // case MonthWeek = 'monthWeek';

    // Month
    case Month = 'month';
    case MonthOfYear = 'monthOfYear';

    // Quarter
    case Quarter = 'quarter';
    case QuarterOfYear = 'quarterOfYear';

    // Year
    case Year = 'year';

    // ISO Week Year
    case IsoWeekYear = 'isoWeekYear';

    //
    // maps cases to the corresponding bin classes
    //

    /**
     * @return class-string<TimeBin|RecurringTimeBin>
     */
    public function getBinClass(): string
    {
        return match ($this) {
            // Hour
            self::Hour => Bin\Hour::class,
            self::HourOfDay => Bin\HourOfDay::class,

            // Date
            self::Date => Bin\Date::class,
            self::IsoWeekDate => Bin\IsoWeekDate::class,
            self::DayOfWeek => Bin\DayOfWeek::class,
            self::DayOfMonth => Bin\DayOfMonth::class,
            self::DayOfYear => Bin\DayOfYear::class,
            self::IsoDayOfWeekYear => Bin\IsoDayOfWeekYear::class,

            // Week
            self::IsoWeek => Bin\IsoWeek::class,
            self::IsoWeekOfYear => Bin\IsoWeekOfYear::class,
            self::WeekOfMonth => Bin\WeekOfMonth::class,

            // Month
            self::Month => Bin\Month::class,
            self::MonthOfYear => Bin\MonthOfYear::class,

            // Quarter
            self::Quarter => Bin\Quarter::class,
            self::QuarterOfYear => Bin\QuarterOfYear::class,

            // WeekYear
            self::IsoWeekYear => Bin\IsoWeekYear::class,

            // Year
            self::Year => Bin\Year::class,
        };
    }

    public function getSqlToCharArgument(): string
    {
        return match ($this) {
            // Hour
            self::Hour => 'YYYYMMDDHH24',
            self::HourOfDay => 'HH24',

            // Date
            self::Date => 'YYYYMMDD',
            self::IsoWeekDate => 'IYYYIWID',
            self::DayOfWeek => 'ID',
            self::DayOfMonth => 'DD',
            self::DayOfYear => 'DDD',
            self::IsoDayOfWeekYear => 'IDDD',

            // Week
            self::IsoWeek => 'IYYYIW',
            self::IsoWeekOfYear => 'IW',
            self::WeekOfMonth => 'W',

            // Month
            self::Month => 'YYYYMM',
            self::MonthOfYear => 'MM',

            // Quarter
            self::Quarter => 'YYYYQ',
            self::QuarterOfYear => 'Q',

            // WeekYear
            self::IsoWeekYear => 'IYYY',

            // Year
            self::Year => 'YYYY',
        };
    }
}
