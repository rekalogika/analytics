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
 * A read-only map of keys to values, that can be iterated and counted. The
 * base of the collections in the result API. Iterating the map yields the
 * keys and the values.
 *
 * Unlike PHP arrays, the keys are not restricted to integers and strings.
 *
 * @template TKey
 * @template-covariant TValue
 * @extends \Traversable<TKey,TValue>
 */
interface Map extends \Traversable, \Countable
{
    /**
     * Returns the value associated with the key, or null if the key is not in
     * the map.
     *
     * @param TKey $key
     * @return TValue|null
     */
    public function get(mixed $key): mixed;

    /**
     * Returns true if the key is in the map.
     *
     * @param TKey $key
     */
    public function has(mixed $key): bool;
}
