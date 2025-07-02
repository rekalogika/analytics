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
use Rekalogika\Analytics\Core\Entity\BaseDimensionGroup;
use Rekalogika\Analytics\Core\GroupingStrategy\FieldSetStrategy;
use Rekalogika\Analytics\Core\Metadata\Dimension;
use Rekalogika\Analytics\Core\Metadata\DimensionGroup;
use Rekalogika\Analytics\Time\Bin\IsoWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeekOfYear;
use Rekalogika\Analytics\Time\Bin\WeekOfMonth;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class WeekSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: IsoWeek::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week'),
        source: new TimeBinValueResolver(TimeBinType::IsoWeek),
    )]
    private ?int $week = null;

    #[Column(
        type: IsoWeekOfYear::TYPE,
        nullable: true,
        enumType: IsoWeekOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week of Year'),
        source: new TimeBinValueResolver(TimeBinType::IsoWeekOfYear),
    )]
    private ?IsoWeekOfYear $weekOfYear = null;

    #[Column(
        type: WeekOfMonth::TYPE,
        nullable: true,
        enumType: WeekOfMonth::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week of Month'),
        source: new TimeBinValueResolver(TimeBinType::WeekOfMonth),
    )]
    private ?WeekOfMonth $weekOfMonth = null;

    //
    // getters
    //

    public function getWeek(): ?IsoWeek
    {
        return $this->getContext()->getUserValue(
            property: 'week',
            class: IsoWeek::class,
        );
    }

    public function getWeekOfYear(): ?IsoWeekOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'weekOfYear',
            class: IsoWeekOfYear::class,
        );
    }

    public function getWeekOfMonth(): ?WeekOfMonth
    {
        return $this->getContext()->getUserValue(
            property: 'weekOfMonth',
            class: WeekOfMonth::class,
        );
    }
}
