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

namespace Rekalogika\Analytics\Model\Hierarchy;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Attribute\Hierarchy;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\DimensionValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\TimeDimensionHierarchy\Date;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfMonth;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfWeek;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Month;
use Rekalogika\Analytics\TimeDimensionHierarchy\MonthOfYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Quarter;
use Rekalogika\Analytics\TimeDimensionHierarchy\QuarterOfYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Week;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekDate;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekOfMonth;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekOfYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Year;
use Rekalogika\Analytics\TimeZoneAwareDimensionHierarchy;
use Rekalogika\Analytics\Util\TranslatableMessage;

#[Embeddable]
#[Hierarchy([
    [600, 500, 400, 200],
    [700, 300, 200],
])]
class DateDimensionHierarchy implements TimeZoneAwareDimensionHierarchy
{
    private \DateTimeZone $timeZone;

    //
    // year
    //

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 600,
        label: new TranslatableMessage('Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Year),
    )]
    private ?int $year = null;

    //
    // quarter
    //

    #[Column(type: Types::INTEGER, nullable: true)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Quarter),
    )]
    private ?int $quarter = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 500,
        label: new TranslatableMessage('Quarter of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::QuarterOfYear),
    )]
    private ?int $quarterOfYear = null;

    //
    // month
    //

    #[Column(type: Types::INTEGER, nullable: true)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Month),
    )]
    private ?int $month = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 400,
        label: new TranslatableMessage('Month of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::MonthOfYear),
    )]
    private ?int $monthOfYear = null;

    //
    // day
    //

    #[Column(type: Types::INTEGER, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Date),
    )]
    private ?int $date = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Week Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekDate),
    )]
    private ?int $weekDate = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfWeek),
    )]
    private ?int $dayOfWeek = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfMonth),
    )]
    private ?int $dayOfMonth = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfYear),
    )]
    private ?int $dayOfYear = null;

    //
    // week year
    //

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 700,
        label: new TranslatableMessage('Week Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekYear),
    )]
    private ?int $weekYear = null;

    //
    // week
    //

    #[Column(type: Types::INTEGER, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Week),
    )]
    private ?int $week = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfYear),
    )]
    private ?int $weekOfYear = null;

    #[Column(type: Types::SMALLINT, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfMonth),
    )]
    private ?int $weekOfMonth = null;

    //
    // Getters and setters
    //

    #[\Override]
    public function setTimeZone(\DateTimeZone $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    public function getTimeZone(): \DateTimeZone
    {
        return $this->timeZone;
    }

    public function getDate(): ?Date
    {
        if ($this->date === null) {
            return null;
        }

        return Date::createFromDatabaseValue($this->date, $this->timeZone);
    }

    public function getWeekDate(): ?WeekDate
    {
        if ($this->weekDate === null) {
            return null;
        }

        return WeekDate::createFromDatabaseValue($this->weekDate, $this->timeZone);
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        if ($this->dayOfWeek === null) {
            return null;
        }

        return DayOfWeek::createFromDatabaseValue($this->dayOfWeek, $this->timeZone);
    }

    public function getDayOfMonth(): ?DayOfMonth
    {
        if ($this->dayOfMonth === null) {
            return null;
        }

        return DayOfMonth::createFromDatabaseValue($this->dayOfMonth, $this->timeZone);
    }

    public function getDayOfYear(): ?DayOfYear
    {
        if ($this->dayOfYear === null) {
            return null;
        }

        return DayOfYear::createFromDatabaseValue($this->dayOfYear, $this->timeZone);
    }

    public function getWeek(): ?Week
    {
        if ($this->week === null) {
            return null;
        }

        return Week::createFromDatabaseValue($this->week, $this->timeZone);
    }

    public function getWeekOfYear(): ?WeekOfYear
    {
        if ($this->weekOfYear === null) {
            return null;
        }

        return WeekOfYear::createFromDatabaseValue($this->weekOfYear, $this->timeZone);
    }

    public function getWeekOfMonth(): ?WeekOfMonth
    {
        if ($this->weekOfMonth === null) {
            return null;
        }

        return WeekOfMonth::createFromDatabaseValue($this->weekOfMonth, $this->timeZone);
    }

    public function getMonth(): ?Month
    {
        if ($this->month === null) {
            return null;
        }

        return Month::createFromDatabaseValue($this->month, $this->timeZone);
    }

    public function getMonthOfYear(): ?MonthOfYear
    {
        if ($this->monthOfYear === null) {
            return null;
        }

        return MonthOfYear::createFromDatabaseValue($this->monthOfYear, $this->timeZone);
    }

    public function getQuarter(): ?Quarter
    {
        if ($this->quarter === null) {
            return null;
        }

        return Quarter::createFromDatabaseValue($this->quarter, $this->timeZone);
    }

    public function getQuarterOfYear(): ?QuarterOfYear
    {
        if ($this->quarterOfYear === null) {
            return null;
        }

        return QuarterOfYear::createFromDatabaseValue($this->quarterOfYear, $this->timeZone);
    }

    public function getYear(): ?Year
    {
        if ($this->year === null) {
            return null;
        }

        return Year::createFromDatabaseValue($this->year, $this->timeZone);
    }

    public function getWeekYear(): ?WeekYear
    {
        if ($this->weekYear === null) {
            return null;
        }

        return WeekYear::createFromDatabaseValue($this->weekYear, $this->timeZone);
    }
}
