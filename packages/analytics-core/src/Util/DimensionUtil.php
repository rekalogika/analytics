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

namespace Rekalogika\Analytics\Util;

use Rekalogika\Analytics\Query\Dimension;

final readonly class DimensionUtil
{
    private function __construct() {}

    public static function isSame(Dimension $a, Dimension $b): bool
    {
        if ($a::class !== $b::class) {
            return false;
        }

        if ($a->getKey() !== $b->getKey()) {
            return false;
        }

        if ($a->getRawMember() !== $b->getRawMember()) {
            return false;
        }

        return true;
    }
}
