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

namespace Rekalogika\Analytics\TimeInterval\Types;

use Rekalogika\Analytics\TimeInterval\DayOfYear;

final class DayOfYearType extends TimeIntervalType
{
    use SmallintTypeTrait;

    #[\Override]
    protected function getClass(): string
    {
        return DayOfYear::class;
    }
}
