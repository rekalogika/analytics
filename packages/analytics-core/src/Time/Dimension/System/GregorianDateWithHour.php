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

namespace Rekalogika\Analytics\Time\Dimension\System;

use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Core\Entity\BaseDimensionGroup;
use Rekalogika\Analytics\Core\GroupingStrategy\RollUpStrategy;
use Rekalogika\Analytics\Core\ValueResolver\Noop;
use Rekalogika\Analytics\Metadata\Attribute\Dimension;
use Rekalogika\Analytics\Metadata\Attribute\DimensionGroup;
use Rekalogika\Analytics\Time\Dimension\Set\HourSet;
use Rekalogika\Analytics\Time\Dimension\System\Trait\GregorianDateTrait;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new RollUpStrategy([
        'year',
        'quarter',
        'month',
        'date',
        'hour',
    ]),
)]
class GregorianDateWithHour extends BaseDimensionGroup
{
    use GregorianDateTrait;

    #[Embedded()]
    #[Dimension(
        label: new TranslatableMessage('Hour'),
        source: new Noop(),
    )]
    private ?HourSet $hour = null;

    public function getHour(): ?HourSet
    {
        return $this->hour;
    }
}
