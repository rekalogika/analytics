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

namespace Rekalogika\Analytics\Contracts\Model;

/**
 * Object that can be compared with another object of the same type.
 */
interface Comparable
{
    /**
     * @template U of Comparable
     * @param U $a
     * @param U $b
     * @return -1|0|1
     */
    public static function compare(
        Comparable $a,
        Comparable $b,
    ): int;
}
