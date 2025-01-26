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

namespace Rekalogika\Analytics;

use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

interface ValueRangeResolver extends ValueResolver
{
    /**
     * DQL expression to get the minimum value.
     */
    public function getMinDQL(QueryContext $context): string;

    /**
     * DQL expression to get the maximum value.
     */
    public function getMaxDQL(QueryContext $context): string;
}
