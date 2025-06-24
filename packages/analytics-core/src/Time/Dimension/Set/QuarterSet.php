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
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\QuarterOfYear;
use Rekalogika\Analytics\Time\TimeBinType;
use Rekalogika\Analytics\Time\ValueResolver\TimeBin;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class QuarterSet implements ContextAwareDimensionGroup
{
    use ContextAwareDimensionGroupTrait;

    //
    // properties
    //

    #[Column(
        type: TimeBinType::TypeQuarter,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Quarter'),
        source: new TimeBin(TimeBinType::Quarter),
    )]
    private ?int $quarter = null;


    #[Column(
        type: TimeBinType::TypeQuarterOfYear,
        nullable: true,
        enumType: QuarterOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Quarter of Year'),
        source: new TimeBin(TimeBinType::QuarterOfYear),
    )]
    private ?QuarterOfYear $quarterOfYear = null;

    //
    // getters
    //

    public function getQuarter(): ?Quarter
    {
        return $this->getContext()->getUserValue(
            property: 'quarter',
            rawValue: $this->quarter,
            class: Quarter::class,
        );
    }

    public function getQuarterOfYear(): ?QuarterOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'quarterOfYear',
            rawValue: $this->quarterOfYear,
            class: QuarterOfYear::class,
        );
    }
}
