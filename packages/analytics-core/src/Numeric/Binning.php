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
 * A binning algorithm, used with `NumericBinValueResolver`. It assigns a
 * number to a bin, identified by an integer index, which is stored in the
 * summary table. The resulting bins are `IndexedBin` instances.
 *
 * The bins are consecutive. Every bin covers the half-open interval from its
 * lower bound (inclusive) to its upper bound (exclusive), and the upper bound
 * of a bin is the lower bound of the next.
 *
 * Implementations must be immutable, and serializable. Two instances with the
 * same serialized form are considered the same binning.
 *
 * @see ValueResolver\NumericBinValueResolver
 * @see Bin\IndexedBin
 */
interface Binning
{
    /**
     * Returns a DQL expression that evaluates to the index of the bin of the
     * input, or null if the input is null.
     *
     * @param string $input The DQL expression of the number to bin.
     */
    public function getDQLExpression(string $input): string;

    /**
     * The lowest index, or null if the bins are unbounded below.
     */
    public function getFirstIndex(): ?int;

    /**
     * The highest index, or null if the bins are unbounded above.
     */
    public function getLastIndex(): ?int;

    /**
     * The lower bound (inclusive) of the bin, or null if it has none.
     */
    public function getLowerBound(int $index): int|float|null;

    /**
     * The upper bound (exclusive) of the bin, or null if it has none.
     */
    public function getUpperBound(int $index): int|float|null;
}
