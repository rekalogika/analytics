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

namespace Rekalogika\Analytics\Model\Hierarchy;

use Doctrine\ORM\Mapping\Embeddable;
use Rekalogika\Analytics\Attribute\Hierarchy;
use Rekalogika\Analytics\Model\Hierarchy\Trait\DayTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\MonthTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\QuarterTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\TimeZoneTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\YearTrait;
use Rekalogika\Analytics\TimeZoneAwareDimensionHierarchy;

#[Embeddable]
#[Hierarchy([
    [600, 400, 200],
])]
final class SimpleDateDimensionHierarchy implements TimeZoneAwareDimensionHierarchy
{
    use TimeZoneTrait;
    use YearTrait;
    use QuarterTrait;
    use MonthTrait;
    use DayTrait;
}
