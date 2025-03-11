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
 * Represent a row in a table
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Row
{
    public function getTuple(): Tuple;

    public function getMeasures(): Measures;
}
