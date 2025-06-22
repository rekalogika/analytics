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
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;
use Rekalogika\Analytics\Core\Entity\ContextAwareHierarchyTrait;
use Rekalogika\Analytics\Core\GroupingStrategy\RollUpStrategy;
use Rekalogika\Analytics\Core\Metadata\DimensionGroup;
use Rekalogika\Analytics\Time\Dimension\System\Trait\IsoWeekDateTrait;

#[Embeddable()]
#[DimensionGroup(
    groupingStrategy: new RollUpStrategy([
        'weekYear',
        'week',
        'weekDate',
    ]),
)]
class IsoWeekDate implements ContextAwareHierarchy
{
    use ContextAwareHierarchyTrait;
    use IsoWeekDateTrait;
}
