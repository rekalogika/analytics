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

namespace Rekalogika\Analytics\Time\Dimension\Set;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Common\Model\TranslatableMessage;
use Rekalogika\Analytics\Contracts\DimensionGroup\ContextAwareDimensionGroup;
use Rekalogika\Analytics\Core\Entity\ContextAwareDimensionGroupTrait;
use Rekalogika\Analytics\Core\GroupingStrategy\FieldSetStrategy;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\DimensionGroup;
use Rekalogika\Analytics\Time\Bin\Week;
use Rekalogika\Analytics\Time\Bin\WeekOfMonth;
use Rekalogika\Analytics\Time\Bin\WeekOfYear;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class WeekSet implements ContextAwareDimensionGroup
{
    use ContextAwareDimensionGroupTrait;

    //
    // properties
    //

    #[Column(
        type: TimeBinType::TypeWeek,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week'),
        source: new TimeBin(TimeBinType::Week),
    )]
    private ?int $week = null;

    #[Column(
        type: TimeBinType::TypeWeekOfYear,
        nullable: true,
        enumType: WeekOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week of Year'),
        source: new TimeBin(TimeBinType::WeekOfYear),
    )]
    private ?WeekOfYear $weekOfYear = null;

    #[Column(
        type: TimeBinType::TypeWeekOfMonth,
        nullable: true,
        enumType: WeekOfMonth::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week of Month'),
        source: new TimeBin(TimeBinType::WeekOfMonth),
    )]
    private ?WeekOfMonth $weekOfMonth = null;

    //
    // getters
    //

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
