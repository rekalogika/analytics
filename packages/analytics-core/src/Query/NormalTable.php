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

namespace Rekalogika\Analytics\Query;

/**
 * A query result in normalized tabular format
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<int,Row>
 */
interface NormalTable extends \Traversable, \Countable
{
    public function first(): ?NormalRow;
}
