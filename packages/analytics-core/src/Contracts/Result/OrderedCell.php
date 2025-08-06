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

namespace Rekalogika\Analytics\Contracts\Result;

/**
 * Represent an ordered cell. An ordered cell is a cell that contains an ordered
 * tuple. In an ordered tuple, the order of dimensions is significant.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface OrderedCell extends Cell
{
    #[\Override]
    public function getTuple(): OrderedTuple;
}
