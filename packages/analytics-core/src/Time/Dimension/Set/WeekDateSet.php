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
use Rekalogika\Analytics\Time\Bin\IsoDayOfWeekYear;
use Rekalogika\Analytics\Time\Bin\IsoWeekDate;
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
        type: IsoWeekDate::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week Date'),
        source: new TimeBinValueResolver(TimeBinType::IsoWeekDate),
    )]
    private ?int $weekDate = null;

    #[Column(
        type: DayOfWeek::TYPE,
        nullable: true,
        enumType: DayOfWeek::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of Week'),
        source: new TimeBinValueResolver(TimeBinType::DayOfWeek),
    )]
    private ?DayOfWeek $dayOfWeek = null;

    #[Column(
        type: IsoDayOfWeekYear::TYPE,
        nullable: true,
        enumType: IsoDayOfWeekYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of WeekYear'),
        source: new TimeBinValueResolver(TimeBinType::IsoDayOfWeekYear),
    )]
    private ?IsoDayOfWeekYear $dayOfWeekYear = null;

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

    public function getDayOfWeekYear(): ?IsoDayOfWeekYear
    {
        return $this->dayOfWeekYear;
    }
}
