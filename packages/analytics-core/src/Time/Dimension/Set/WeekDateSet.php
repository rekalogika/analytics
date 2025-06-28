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
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfWeekYear;
use Rekalogika\Analytics\Time\Bin\WeekDate;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class WeekDateSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: TimeBinType::TypeWeekDate,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week Date'),
        source: new TimeBinValueResolver(TimeBinType::WeekDate),
    )]
    private ?int $weekDate = null;

    #[Column(
        type: TimeBinType::TypeDayOfWeek,
        nullable: true,
        enumType: DayOfWeek::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of Week'),
        source: new TimeBinValueResolver(TimeBinType::DayOfWeek),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(
        type: TimeBinType::TypeDayOfWeek,
        nullable: true,
        enumType: DayOfWeekYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of WeekYear'),
        source: new TimeBinValueResolver(TimeBinType::DayOfWeekYear),
    )]
    private ?DayOfWeekYear $dayOfWeekYear = null;

    //
    // getters
    //

    public function getWeekDate(): ?WeekDate
    {
        return $this->getContext()->getUserValue(
            property: 'weekDate',
            class: WeekDate::class,
        );
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function getDayOfWeekYear(): ?DayOfWeekYear
    {
        return $this->dayOfWeekYear;
    }
}
