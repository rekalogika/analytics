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

/**
 * A numeric bin defined by the user as a backed enum, one case for each bin,
 * declared in ascending order. Use it with `RangeBinValueResolver`, and
 * `RangeBinTrait` for the implementation.
 *
 * The thresholds separate the cases. With the thresholds `[26, 36]` and three
 * cases, the first case gets the values below 26, the second gets the values
 * from 26 to below 36, and the third gets 36 and above.
 *
 * The enum can implement `TranslatableInterface` to provide the labels of the
 * bins.
 *
 * @see Bin\Trait\RangeBinTrait
 * @see ValueResolver\RangeBinValueResolver
 */
interface RangeBin extends NumericBin, \BackedEnum
{
    /**
     * The lower bounds (inclusive) of every case except the first, in
     * ascending order. It must have exactly one element fewer than the cases.
     *
     * @return list<int|float>
     */
    public static function getThresholds(): array;
}
