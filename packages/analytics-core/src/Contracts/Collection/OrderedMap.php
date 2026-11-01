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

namespace Rekalogika\Analytics\Contracts\Collection;

/**
 * A map whose entries have a defined order, so the values can also be accessed
 * by their position. Iteration follows the same order.
 *
 * @template TKey
 * @template-covariant TValue
 * @extends Map<TKey,TValue>
 */
interface OrderedMap extends Map
{
    /**
     * Returns the value at the given zero-based position, or null if the
     * position is out of range.
     *
     * @param int<0,max> $index
     * @return TValue|null
     */
    public function getByIndex(int $index): mixed;

    /**
     * Returns the first value, or null if the map is empty.
     *
     * @return TValue|null
     */
    public function first(): mixed;

    /**
     * Returns the last value, or null if the map is empty.
     *
     * @return TValue|null
     */
    public function last(): mixed;
}
