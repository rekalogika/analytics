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
use Rekalogika\Analytics\Time\Bin\Gregorian\Month;
use Rekalogika\Analytics\Time\Bin\Gregorian\MonthOfYear;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class MonthSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: Month::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Month'),
        source: new TimeBinValueResolver(Month::class),
    )]
    private ?int $month = null;

    #[Column(
        type: MonthOfYear::TYPE,
        nullable: true,
        enumType: MonthOfYear::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Month of Year'),
        source: new TimeBinValueResolver(MonthOfYear::class),
    )]
    private ?MonthOfYear $monthOfYear = null;

    //
    // getters
    //

    public function getMonth(): ?Month
    {
        return $this->getContext()->getUserValue(
            property: 'month',
            class: Month::class,
        );
    }

    public function getMonthOfYear(): ?MonthOfYear
    {
        return $this->getContext()->getUserValue(
            property: 'monthOfYear',
            class: MonthOfYear::class,
        );
    }
}
