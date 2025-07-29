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
 * @template-covariant T
 * @extends \Traversable<int,T>
 */
interface ListCollection extends \Traversable, \Countable
{
    /**
     * @return T|null
     */
    public function get(int $key): mixed;

    /**
     * @return T|null
     */
    public function first(): mixed;

    /**
     * @return T|null
     */
    public function last(): mixed;
}
