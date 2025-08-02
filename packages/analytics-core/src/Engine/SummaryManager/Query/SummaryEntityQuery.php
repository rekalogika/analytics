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

namespace Rekalogika\Analytics\Engine\SummaryManager\Query;

use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\SimpleQueryBuilder\DecomposedQuery;

interface SummaryEntityQuery
{
    public function withBoundary(Partition $start, Partition $end): static;

    /**
     * @return iterable<DecomposedQuery>
     */
    public function getQueries(): iterable;
}
