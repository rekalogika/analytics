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

namespace Rekalogika\Analytics\Time\Model\Hierarchy;

use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Attribute\Hierarchy;
use Rekalogika\Analytics\Contracts\Hierarchy\ContextAwareHierarchy;
use Rekalogika\Analytics\Time\Model\Hierarchy\Trait\ContextAwareHierarchyTrait;
use Rekalogika\Analytics\Time\Model\Hierarchy\Trait\DayTrait;
use Rekalogika\Analytics\Time\Model\Hierarchy\Trait\MonthTrait;
use Rekalogika\Analytics\Time\Model\Hierarchy\Trait\TimeZoneTrait;
use Rekalogika\Analytics\Time\Model\Hierarchy\Trait\YearTrait;

#[Embeddable]
#[Hierarchy([
    [600, 400, 200],
])]
final class SimpleDateDimensionHierarchy implements ContextAwareHierarchy
{
    use ContextAwareHierarchyTrait;
    use TimeZoneTrait;
    use YearTrait;
    use MonthTrait;
    use DayTrait;
}
