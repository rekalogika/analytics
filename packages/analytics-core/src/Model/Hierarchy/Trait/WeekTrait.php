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
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekOfMonthType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekOfYearType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Types\WeekType;
use Rekalogika\Analytics\TimeDimensionHierarchy\Week;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekOfMonth;
use Rekalogika\Analytics\TimeDimensionHierarchy\WeekOfYear;
use Rekalogika\Analytics\Util\TranslatableMessage;

trait WeekTrait
{
    abstract public function getTimeZone(): \DateTimeZone;

    #[Column(type: WeekType::class, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::Week),
    )]
    private ?Week $week = null;

    #[Column(type: WeekOfYearType::class, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Year'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfYear),
    )]
    private ?WeekOfYear $weekOfYear = null;

    #[Column(type: WeekOfMonthType::class, nullable: true)]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Month'),
        valueResolver: new TimeDimensionValueResolver(TimeFormat::WeekOfMonth),
    )]
    private ?WeekOfMonth $weekOfMonth = null;

    public function getWeek(): ?Week
    {
        return $this->week?->withTimeZone($this->getTimeZone());
    }

    public function getWeekOfYear(): ?WeekOfYear
    {
        return $this->weekOfYear?->withTimeZone($this->getTimeZone());
    }

    public function getWeekOfMonth(): ?WeekOfMonth
    {
        return $this->weekOfMonth?->withTimeZone($this->getTimeZone());
    }
}
