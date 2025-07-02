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
use Rekalogika\Analytics\Time\Bin\Quarter;
use Rekalogika\Analytics\Time\Bin\QuarterOfYear;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class QuarterSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: Quarter::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Quarter'),
        source: new TimeBinValueResolver(Quarter::class),
    )]
    private ?int $quarter = null;


    #[Column(
        type: QuarterOfYear::TYPE,
        nullable: true,
        enumType: QuarterOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Quarter of Year'),
        source: new TimeBinValueResolver(QuarterOfYear::class),
    )]
    private ?QuarterOfYear $quarterOfYear = null;

    //
    // getters
    //

    public function getQuarter(): ?Quarter
    {
        return $this->getContext()->getUserValue(
            property: 'quarter',
            class: Quarter::class,
        );
    }

    public function getQuarterOfYear(): ?QuarterOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'quarterOfYear',
            class: QuarterOfYear::class,
        );
    }
}
