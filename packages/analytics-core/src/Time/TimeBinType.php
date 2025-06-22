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

    public const TypeHour = Types::INTEGER;
    public const TypeHourOfDay = Types::SMALLINT;

    public const TypeDate = Types::INTEGER;
    public const TypeWeekDate = Types::INTEGER;
    public const TypeDayOfWeek = Types::SMALLINT;
    public const TypeDayOfMonth = Types::SMALLINT;
    public const TypeDayOfYear = Types::SMALLINT;
    public const TypeDayOfWeekYear = Types::SMALLINT;

    public const TypeWeek = Types::INTEGER;
    public const TypeWeekOfYear = Types::SMALLINT;
    public const TypeWeekOfMonth = Types::SMALLINT;

    public const TypeMonth = Types::INTEGER;
    public const TypeMonthOfYear = Types::SMALLINT;

    public const TypeQuarter = Types::INTEGER;
    public const TypeQuarterOfYear = Types::SMALLINT;

    public const TypeWeekYear = Types::SMALLINT;
    public const TypeYear = Types::SMALLINT;

    //
    // enum cases
    //

    case Hour = 'hour';
    case HourOfDay = 'hourOfDay';

    case Date = 'date';
    case DayOfWeek = 'dayOfWeek';
    case DayOfMonth = 'dayOfMonth';
    case DayOfYear = 'dayOfYear';
    case DayOfWeekYear = 'dayOfWeekYear';

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

    public function getDoctrineType(): string
    {
        return match ($this) {
            // HourTrait
            self::Hour => self::TypeHour,
            self::HourOfDay => self::TypeHourOfDay,

            // DayTrait
            self::Date => self::TypeDate,
            self::WeekDate => self::TypeWeekDate,
            self::DayOfWeek => self::TypeDayOfWeek,
            self::DayOfMonth => self::TypeDayOfMonth,
            self::DayOfYear => self::TypeDayOfYear,
            self::DayOfWeekYear => self::TypeDayOfWeekYear,

            // WeekTrait
            self::Week => self::TypeWeek,
            self::WeekOfYear => self::TypeWeekOfYear,
            self::WeekOfMonth => self::TypeWeekOfMonth,

            // MonthTrait
            self::Month => self::TypeMonth,
            self::MonthOfYear => self::TypeMonthOfYear,

            // QuarterTrait
            self::Quarter => self::TypeQuarter,
            self::QuarterOfYear => self::TypeQuarterOfYear,

            // WeekYearTrait
            self::WeekYear => self::TypeWeekYear,

            // YearTrait
            self::Year => self::TypeYear,
        };
    }

    /**
     * @return class-string<TimeBin|RecurringTimeBin>
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
            self::DayOfWeekYear => Bin\DayOfWeekYear::class,

            // WeekTrait
            self::Week => Bin\Week::class,
            self::WeekOfYear => Bin\WeekOfYear::class,
            self::WeekOfMonth => Bin\WeekOfMonth::class,

            // MonthTrait
            self::Month => Bin\Month::class,
            self::MonthOfYear => Bin\MonthOfYear::class,

            // QuarterTrait
            self::Quarter => Bin\Quarter::class,
            self::QuarterOfYear => Bin\QuarterOfYear::class,

            // WeekYearTrait
            self::WeekYear => Bin\WeekYear::class,

            // YearTrait
            self::Year => Bin\Year::class,
        };
    }
}
