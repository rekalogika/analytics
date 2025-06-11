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

namespace Rekalogika\Analytics\PostgreSQLHll\Doctrine\Function;

/**
 * REKALOGIKA_HLL_UNION_AGG
 */
final class HllUnionAggregateFunction extends AbstractSimpleFunction
{
    #[\Override]
    public function getFunctionName(): string
    {
        return 'hll_union_agg';
    }
}
