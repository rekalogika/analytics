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

interface DimensionValueResolver
{
    /**
     * DQL expression to calculate the value to be stored in the dimension
     * column.
     */
    public function getDQL(
        string $input,
        DimensionValueResolverContext $context,
    ): string;
}
