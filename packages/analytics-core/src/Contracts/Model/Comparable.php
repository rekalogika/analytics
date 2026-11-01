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
     * Compares two objects of the same class. The framework uses this to sort
     * dimension members, and to determine the direction of a sequence.
     *
     * @template U of Comparable
     * @param U $a
     * @param U $b
     * @return -1|0|1 -1 if `$a` is less than `$b`, 0 if they are equal, 1 if
     * `$a` is greater than `$b`.
     */
    public static function compare(
        Comparable $a,
        Comparable $b,
    ): int;
}
