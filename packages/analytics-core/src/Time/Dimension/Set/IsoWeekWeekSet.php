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
use Rekalogika\Analytics\Metadata\Attribute\Dimension;
use Rekalogika\Analytics\Metadata\Attribute\DimensionGroup;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekWeek;
use Rekalogika\Analytics\Time\Bin\IsoWeek\IsoWeekWeekOfYear;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class IsoWeekWeekSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: IsoWeekWeek::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week'),
        source: new TimeBinValueResolver(IsoWeekWeek::class),
    )]
    private ?int $week = null;

    #[Column(
        type: IsoWeekWeekOfYear::TYPE,
        nullable: true,
        enumType: IsoWeekWeekOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Week of Year'),
        source: new TimeBinValueResolver(IsoWeekWeekOfYear::class),
    )]
    private ?IsoWeekWeekOfYear $weekOfYear = null;

    //
    // getters
    //

    public function getWeek(): ?IsoWeekWeek
    {
        return $this->getContext()->getUserValue(
            property: 'week',
            class: IsoWeekWeek::class,
        );
    }

    public function getWeekOfYear(): ?IsoWeekWeekOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'weekOfYear',
            class: IsoWeekWeekOfYear::class,
        );
    }
}
