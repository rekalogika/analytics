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
    case DayOfWeekYear = 'dayOfWeekYear';
    case WeekDate = 'weekDate';

    // Week
    case Week = 'week';
    case WeekOfYear = 'weekOfYear';
    case WeekOfMonth = 'weekOfMonth';

    // Month
    case Month = 'month';
    case MonthOfYear = 'monthOfYear';

    // Quarter
    case Quarter = 'quarter';
    case QuarterOfYear = 'quarterOfYear';

    // Year
    case Year = 'year';

    // ISO Week Year
    case WeekYear = 'weekYear';

    //
    // doctrine types
    //

    // Hour
    public const TypeHour = Types::INTEGER;
    public const TypeHourOfDay = Types::SMALLINT;

    // Date
    public const TypeDate = Types::INTEGER;
    public const TypeWeekDate = Types::INTEGER;
    public const TypeDayOfWeek = Types::SMALLINT;
    public const TypeDayOfMonth = Types::SMALLINT;
    public const TypeDayOfYear = Types::SMALLINT;
    public const TypeDayOfWeekYear = Types::SMALLINT;

    // Week
    public const TypeWeek = Types::INTEGER;
    public const TypeWeekOfYear = Types::SMALLINT;
    public const TypeWeekOfMonth = Types::SMALLINT;

    // Month
    public const TypeMonth = Types::INTEGER;
    public const TypeMonthOfYear = Types::SMALLINT;

    // Quarter
    public const TypeQuarter = Types::INTEGER;
    public const TypeQuarterOfYear = Types::SMALLINT;

    // Year
    public const TypeYear = Types::SMALLINT;

    // ISO Week Year
    public const TypeWeekYear = Types::SMALLINT;

    //
    // maps types to doctrine types
    //

    public function getDoctrineType(): string
    {
        return match ($this) {
            // Hour
            self::Hour => self::TypeHour,
            self::HourOfDay => self::TypeHourOfDay,

            // Date
            self::Date => self::TypeDate,
            self::WeekDate => self::TypeWeekDate,
            self::DayOfWeek => self::TypeDayOfWeek,
            self::DayOfMonth => self::TypeDayOfMonth,
            self::DayOfYear => self::TypeDayOfYear,
            self::DayOfWeekYear => self::TypeDayOfWeekYear,

            // Week
            self::Week => self::TypeWeek,
            self::WeekOfYear => self::TypeWeekOfYear,
            self::WeekOfMonth => self::TypeWeekOfMonth,

            // Month
            self::Month => self::TypeMonth,
            self::MonthOfYear => self::TypeMonthOfYear,

            // Quarter
            self::Quarter => self::TypeQuarter,
            self::QuarterOfYear => self::TypeQuarterOfYear,

            // WeekYear
            self::WeekYear => self::TypeWeekYear,

            // Year
            self::Year => self::TypeYear,
        };
    }

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
            self::WeekDate => Bin\IsoWeekDate::class,
            self::DayOfWeek => Bin\DayOfWeek::class,
            self::DayOfMonth => Bin\DayOfMonth::class,
            self::DayOfYear => Bin\DayOfYear::class,
            self::DayOfWeekYear => Bin\IsoDayOfWeekYear::class,

            // Week
            self::Week => Bin\IsoWeek::class,
            self::WeekOfYear => Bin\IsoWeekOfYear::class,
            self::WeekOfMonth => Bin\WeekOfMonth::class,

            // Month
            self::Month => Bin\Month::class,
            self::MonthOfYear => Bin\MonthOfYear::class,

            // Quarter
            self::Quarter => Bin\Quarter::class,
            self::QuarterOfYear => Bin\QuarterOfYear::class,

            // WeekYear
            self::WeekYear => Bin\IsoWeekYear::class,

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
            self::WeekDate => 'IYYYIWID',
            self::DayOfWeek => 'ID',
            self::DayOfMonth => 'DD',
            self::DayOfYear => 'DDD',
            self::DayOfWeekYear => 'IDDD',

            // Week
            self::Week => 'IYYYIW',
            self::WeekOfYear => 'IW',
            self::WeekOfMonth => 'W',

            // Month
            self::Month => 'YYYYMM',
            self::MonthOfYear => 'MM',

            // Quarter
            self::Quarter => 'YYYYQ',
            self::QuarterOfYear => 'Q',

            // WeekYear
            self::WeekYear => 'IYYY',

            // Year
            self::Year => 'YYYY',
        };
    }
}
