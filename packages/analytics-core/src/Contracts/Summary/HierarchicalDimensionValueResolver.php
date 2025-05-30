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

namespace Rekalogika\Analytics\Contracts\Summary;

interface HierarchicalDimensionValueResolver
{
    /**
     * DQL expression to calculate the value to be stored in the dimension
     * column. This is used inside the embeddable class of a hierarchical
     * dimension.
     *
     * $input is the ValueResolver of the dimension, defined on the source
     * entity.
     */
    public function getDQL(
        ValueResolver $input,
        Context $context,
    ): string;
}
