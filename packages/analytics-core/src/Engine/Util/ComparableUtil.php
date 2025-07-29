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

namespace Rekalogika\Analytics\Engine\Util;

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\Comparable;

final readonly class ComparableUtil
{
    private function __construct() {}

    /**
     * @template T of Comparable
     * @param T $a
     * @param T $b
     * @return -1|0|1
     */
    public static function compare(Comparable $a, Comparable $b): int
    {
        if ($a::compare(...) !== $b::compare(...)) {
            throw new UnexpectedValueException(\sprintf(
                'Cannot compare "%s" with "%s".',
                $a::class,
                $b::class,
            ));
        }

        return $a::compare($a, $b);
    }

    /**
     * Sorts the given array of Comparable items.
     *
     * @template T of Comparable
     * @param list<T> $items
     * @return list<T>
     */
    public static function sort(
        array $items,
        Order $order = Order::Ascending,
    ): array {

        if (empty($items)) {
            return $items;
        }

        usort($items, static function (Comparable $a, Comparable $b) use ($order): int {
            $comparison = $a::compare($a, $b);

            return $order === Order::Ascending ? $comparison : -$comparison;
        });


        return $items;
    }
}
