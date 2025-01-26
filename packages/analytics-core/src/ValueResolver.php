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

interface ValueResolver
{
    /**
     * DQL expression to transform source value to summary table value.
     */
    public function getDQL(QueryContext $context): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;

    /**
     * transform from source value to summary value
     *
     */
    public function transform(mixed $value): mixed;
}
