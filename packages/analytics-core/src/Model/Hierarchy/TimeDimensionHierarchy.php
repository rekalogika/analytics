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
use Rekalogika\Analytics\Contracts\Summary\TimeZoneAwareDimensionHierarchy;
use Rekalogika\Analytics\Model\Hierarchy\Trait\DayTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\HourTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\MonthTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\QuarterTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\TimeZoneTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\WeekTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\WeekYearTrait;
use Rekalogika\Analytics\Model\Hierarchy\Trait\YearTrait;

#[Embeddable]
#[Hierarchy([
    [600, 500, 400, 200, 100],
    [700, 300, 200, 100],
])]
final class TimeDimensionHierarchy implements TimeZoneAwareDimensionHierarchy
{
    use TimeZoneTrait;
    use YearTrait;
    use QuarterTrait;
    use MonthTrait;
    use DayTrait;
    use HourTrait;
    use WeekYearTrait;
    use WeekTrait;
}
