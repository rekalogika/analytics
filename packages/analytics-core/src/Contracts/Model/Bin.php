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
 * Represents a bin, which is the result of a data binning operation.
 *
 * @template T
 */
interface Bin
{
    public function getNext(): ?static;

    public function getPrevious(): ?static;

    /**
     * @template U of Bin
     * @param U $a
     * @param U $b
     * @return -1|0|1
     */
    public static function compare(
        Bin $a,
        Bin $b,
    ): int;
}
