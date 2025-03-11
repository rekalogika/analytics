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

namespace Rekalogika\Analytics;

/**
 * Determines the numeric value out of the value and raw value of a measure.
 */
interface NumericValueResolver
{
    public function resolveNumericValue(mixed $value, mixed $rawValue): int|float;
}
