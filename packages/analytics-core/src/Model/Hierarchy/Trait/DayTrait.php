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

namespace Rekalogika\Analytics\Model\Hierarchy\Trait;

use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\DimensionValueResolver\TimeDimensionValueResolver;
use Rekalogika\Analytics\DimensionValueResolver\TimeFormat;
use Rekalogika\Analytics\TimeDimensionHierarchy\Date;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfMonth;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfWeek;
use Rekalogika\Analytics\TimeDimensionHierarchy\DayOfYear;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DateType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfMonthType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfWeekType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\DayOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekDateType;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekDate;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait DayTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: DateType::class, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Date),
    )]
    private ?Date $date = null;

    #[Column(type: WeekDateType::class, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Week Date'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekDate),
    )]
    private ?WeekDate $weekDate = null;

    #[Column(type: DayOfWeekType::class, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfWeek),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(type: DayOfMonthType::class, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfMonth),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[Column(type: DayOfYearType::class, nullable: true)]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::DayOfYear),
    )]
    private ?DayOfYear $dayOfYear = null;

    public function getDate(): ?Date
    {
        return $this->date?->withTimeZone($this->getTimeZone());
    }

    public function getWeekDate(): ?WeekDate
    {
        return $this->weekDate?->withTimeZone($this->getTimeZone());
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek?->withTimeZone($this->getTimeZone());
    }

    public function getDayOfMonth(): ?DayOfMonth
    {
        return $this->dayOfMonth?->withTimeZone($this->getTimeZone());
    }

    public function getDayOfYear(): ?DayOfYear
    {
        return $this->dayOfYear?->withTimeZone($this->getTimeZone());
    }
}
