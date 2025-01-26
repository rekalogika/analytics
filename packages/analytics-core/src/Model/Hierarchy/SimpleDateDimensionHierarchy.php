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
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekDate;
use Rekalogika\Analytics\TimeDimensionHierarchy\Year;
use Rekalogika\Analytics\TimeZoneAwareDimensionHierarchy;
use Symfony\Component\Translation\TranslatableMessage;

#[Embeddable]
#[Hierarchy([
    [600, 400, 200],
])]
class SimpleDateDimensionHierarchy implements TimeZoneAwareDimensionHierarchy
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

    public function getYear(): ?Year
    {
        if ($this->year === null) {
            return null;
        }

        return Year::createFromDatabaseValue($this->year, $this->timeZone);
    }
}
