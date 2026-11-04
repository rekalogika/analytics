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

namespace Rekalogika\Analytics\Numeric;

use Rekalogika\Analytics\Contracts\Model\Bin;

/**
 * A bin of a number, e.g. an age group or a price range. It covers the
 * half-open interval from the lower bound (inclusive) to the upper bound
 * (exclusive). A null bound means the bin is unbounded on that side, e.g. the
 * first bin of "under 26".
 *
 * @extends Bin<int|float>
 */
interface NumericBin extends Bin
{
    /**
     * The lowest value in the bin (inclusive), or null if the bin has no lower
     * bound.
     */
    public function getLowerBound(): int|float|null;

    /**
     * The value immediately above the bin (exclusive), or null if the bin has
     * no upper bound.
     */
    public function getUpperBound(): int|float|null;
}
