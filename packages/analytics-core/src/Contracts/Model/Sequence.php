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
 * Represents a sequence in a data set, which is a series of ordered elements.
 */
interface Sequence
{
    public function getNext(): ?static;

    public function getPrevious(): ?static;

    /**
     * @template U of Sequence
     * @param U $a
     * @param U $b
     * @return -1|0|1
     */
    public static function compare(
        Sequence $a,
        Sequence $b,
    ): int;
}
