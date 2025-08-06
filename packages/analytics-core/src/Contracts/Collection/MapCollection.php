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
 * @template TKey
 * @template-covariant TValue
 * @extends \Traversable<TKey,TValue>
 */
interface MapCollection extends \Traversable, \Countable
{
    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function get(mixed $key): mixed;

    /**
     * @param TKey $key
     */
    public function has(mixed $key): bool;
}
