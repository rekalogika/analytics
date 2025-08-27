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
 * @extends Map<TKey,TValue>
 */
interface OrderedMap extends Map
{
    /**
     * @param int<0,max> $index
     * @return TValue|null
     */
    public function getByIndex(int $index): mixed;

    /**
     * @return TValue|null
     */
    public function first(): mixed;

    /**
     * @return TValue|null
     */
    public function last(): mixed;
}
