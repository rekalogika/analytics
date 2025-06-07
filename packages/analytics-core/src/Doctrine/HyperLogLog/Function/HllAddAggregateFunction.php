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

namespace Rekalogika\Analytics\Doctrine\HyperLogLog\Function;

/**
 * REKALOGIKA_HLL_ADD_AGG
 */
final class HllAddAggregateFunction extends AbstractSimpleFunction
{
    #[\Override]
    public function getFunctionName(): string
    {
        return 'hll_add_agg';
    }
}
