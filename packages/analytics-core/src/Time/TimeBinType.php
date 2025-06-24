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
            self::WeekDate => Bin\WeekDate::class,
            self::DayOfWeek => Bin\DayOfWeek::class,
            self::DayOfMonth => Bin\DayOfMonth::class,
            self::DayOfYear => Bin\DayOfYear::class,
            self::DayOfWeekYear => Bin\DayOfWeekYear::class,

            // Week
            self::Week => Bin\Week::class,
            self::WeekOfYear => Bin\WeekOfYear::class,
            self::WeekOfMonth => Bin\WeekOfMonth::class,

            // Month
            self::Month => Bin\Month::class,
            self::MonthOfYear => Bin\MonthOfYear::class,

            // Quarter
            self::Quarter => Bin\Quarter::class,
            self::QuarterOfYear => Bin\QuarterOfYear::class,

            // WeekYear
            self::WeekYear => Bin\WeekYear::class,

            // Year
            self::Year => Bin\Year::class,
        };
    }
}
