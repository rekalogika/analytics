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

namespace Rekalogika\Analytics\Time\Hierarchy\Trait;

use Doctrine\ORM\Mapping\Column;
use Rekalogika\Analytics\Contracts\Common\TranslatableMessage;
use Rekalogika\Analytics\Contracts\Context\HierarchyContext;
use Rekalogika\Analytics\Contracts\Metadata\LevelProperty;
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Bin\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfYear;
use Rekalogika\Analytics\Time\Bin\WeekDate;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait DayTrait
{
    abstract private function getContext(): HierarchyContext;

    #[Column(
        type: TimeBinType::TypeDate,
        nullable: true,
    )]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Date'),
        valueResolver: new TimeBin(TimeBinType::Date),
    )]
    private ?int $date = null;

    #[Column(
        type: TimeBinType::TypeWeekDate,
        nullable: true,
    )]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Week Date'),
        valueResolver: new TimeBin(TimeBinType::WeekDate),
    )]
    private ?int $weekDate = null;

    #[Column(
        type: TimeBinType::TypeDayOfWeek,
        nullable: true,
        enumType: DayOfWeek::class,
    )]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Week'),
        valueResolver: new TimeBin(TimeBinType::DayOfWeek),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(
        type: TimeBinType::TypeDayOfMonth,
        nullable: true,
        enumType: DayOfMonth::class,
    )]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Month'),
        valueResolver: new TimeBin(TimeBinType::DayOfMonth),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[Column(
        type: TimeBinType::TypeDayOfYear,
        nullable: true,
        enumType: DayOfYear::class,
    )]
    #[LevelProperty(
        level: 200,
        label: new TranslatableMessage('Day of Year'),
        valueResolver: new TimeBin(TimeBinType::DayOfYear),
    )]
    private ?DayOfYear $dayOfYear = null;

    public function getDate(): ?Date
    {
        return $this->getContext()->getUserValue(
            property: 'date',
            rawValue: $this->date,
            class: Date::class,
        );
    }

    public function getWeekDate(): ?WeekDate
    {
        return $this->getContext()->getUserValue(
            property: 'weekDate',
            rawValue: $this->weekDate,
            class: WeekDate::class,
        );
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function getDayOfMonth(): ?DayOfMonth
    {
        return $this->dayOfMonth;
    }

    public function getDayOfYear(): ?DayOfYear
    {
        return $this->dayOfYear;
    }
}
