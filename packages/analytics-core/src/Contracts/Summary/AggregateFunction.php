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

interface AggregateFunction
{
    /**
     * Gets the DQL expression to transform source values into an aggregate.
     */
    public function getSourceToAggregateDQLExpression(Context $context): ?string;

    /**
     * Gets the DQL expression to roll up data from multiple aggregate fields
     * into a larger aggregate.
     */
    public function getAggregateToAggregateDQLExpression(): ?string;

    /**
     * Gets the DQL expression to convert an aggregate field into a value for
     * human consumption.
     */
    public function getAggregateToResultDQLExpression(SummaryContext $context): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
