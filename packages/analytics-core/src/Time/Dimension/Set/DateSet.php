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
use Rekalogika\Analytics\Time\Bin\Date;
use Rekalogika\Analytics\Time\Bin\DayOfMonth;
use Rekalogika\Analytics\Time\Bin\DayOfWeek;
use Rekalogika\Analytics\Time\Bin\DayOfYear;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class DateSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: Date::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Date'),
        source: new TimeBinValueResolver(Date::class),
    )]
    private ?int $date = null;

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
        type: DayOfMonth::TYPE,
        nullable: true,
        enumType: DayOfMonth::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of Month'),
        source: new TimeBinValueResolver(DayOfMonth::class),
    )]
    private ?DayOfMonth $dayOfMonth = null;

    #[Column(
        type: DayOfYear::TYPE,
        nullable: true,
        enumType: DayOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Day of Year'),
        source: new TimeBinValueResolver(DayOfYear::class),
    )]
    private ?DayOfYear $dayOfYear = null;

    //
    // getters
    //

    public function getDate(): ?Date
    {
        return $this->getContext()->getUserValue(
            property: 'date',
            class: Date::class,
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
