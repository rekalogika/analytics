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
use Rekalogika\Analytics\Time\Bin\Gregorian\Hour;
use Rekalogika\Analytics\Time\Bin\Gregorian\HourOfDay;
use Rekalogika\Analytics\Time\ValueResolver\TimeBinValueResolver;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new FieldSetStrategy(),
)]
class HourSet extends BaseDimensionGroup
{
    //
    // properties
    //

    #[Column(
        type: Hour::TYPE,
        nullable: true,
    )]
    #[Dimension(
        label: new TranslatableMessage('Hour'),
        source: new TimeBinValueResolver(Hour::class),
    )]
    private ?int $hour = null;

    #[Column(
        type: HourOfDay::TYPE,
        nullable: true,
        enumType: HourOfDay::class,
    )]
    #[Dimension(
        label: new TranslatableMessage('Hour of Day'),
        source: new TimeBinValueResolver(HourOfDay::class),
    )]
    private ?HourOfDay $hourOfDay = null;

    //
    // getters
    //

    public function getHour(): ?Hour
    {
        return $this->getContext()->getUserValue(
            property: 'hour',
            class: Hour::class,
        );
    }

    public function getHourOfDay(): ?HourOfDay
    {
        return $this->getContext()->getUserValue(
            property: 'hourOfDay',
            class: HourOfDay::class,
        );
    }
}
