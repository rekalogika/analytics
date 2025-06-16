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
use Rekalogika\Analytics\Time\Bin\Week;
use Rekalogika\Analytics\Time\Bin\WeekOfMonth;
use Rekalogika\Analytics\Time\Bin\WeekOfYear;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

trait WeekTrait
{
    abstract private function getContext(): HierarchyContext;

    #[Column(
        type: TimeBinType::TypeWeek,
        nullable: true,
    )]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week'),
        valueResolver: new TimeBin(TimeBinType::Week),
    )]
    private ?Week $week = null;

    #[Column(
        type: TimeBinType::TypeWeekOfYear,
        nullable: true,
        enumType: WeekOfYear::class,
    )]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Year'),
        valueResolver: new TimeBin(TimeBinType::WeekOfYear),
    )]
    private ?WeekOfYear $weekOfYear = null;

    #[Column(
        type: TimeBinType::TypeWeekOfMonth,
        nullable: true,
        enumType: WeekOfMonth::class,
    )]
    #[LevelProperty(
        level: 300,
        label: new TranslatableMessage('Week of Month'),
        valueResolver: new TimeBin(TimeBinType::WeekOfMonth),
    )]
    private ?WeekOfMonth $weekOfMonth = null;

    public function getWeek(): ?Week
    {
        return $this->getContext()->getUserValue(
            property: 'week',
            rawValue: $this->week,
            class: Week::class,
        );
    }

    public function getWeekOfYear(): ?WeekOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'weekOfYear',
            rawValue: $this->weekOfYear,
            class: WeekOfYear::class,
        );
    }

    public function getWeekOfMonth(): ?WeekOfMonth
    {
        return $this->getContext()->getUserValue(
            property: 'weekOfMonth',
            rawValue: $this->weekOfMonth,
            class: WeekOfMonth::class,
        );
    }
}
