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
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Core\Entity\BaseDimensionGroup;
use Rekalogika\Analytics\Core\GroupingStrategy\FieldSetStrategy;
use Rekalogika\Analytics\Metadata\Attribute\Dimension;
use Rekalogika\Analytics\Metadata\Attribute\DimensionGroup;
use Rekalogika\Analytics\Time\Bin\Gregorian\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekDate;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekDayOfYear;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class IsoWeekDateSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: IsoWeekDate::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week Date'),
        source: new TimeBinValueResolver(IsoWeekDate::class),
    )]
    private ?int $weekDate = null;

    #[Column(
        type: DayOfWeek::TYPE,
        nullable: true,
        enumType: DayOfWeek::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of Week'),
        source: new TimeBinValueResolver(DayOfWeek::class),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(
        type: IsoWeekDayOfYear::TYPE,
        nullable: true,
        enumType: IsoWeekDayOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of WeekYear'),
        source: new TimeBinValueResolver(IsoWeekDayOfYear::class),
    )]
    private ?IsoWeekDayOfYear $dayOfWeekYear = null;

    //
    // getters
    //

    public function getWeekDate(): ?IsoWeekDate
    {
        return $this->getContext()->getUserValue(
            property: 'weekDate',
            class: IsoWeekDate::class,
        );
    }

    public function getDayOfWeek(): ?DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function getDayOfWeekYear(): ?IsoWeekDayOfYear
    {
        return $this->dayOfWeekYear;
    }
}
