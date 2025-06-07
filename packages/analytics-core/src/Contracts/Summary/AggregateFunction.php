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
    public function getSourceToAggregateDQLExpression(
        Context $context,
    ): ?string;

    /**
     * Gets the DQL expression to combine data from multiple aggregate fields
     * into one larger aggregate.
     *
     * @param string $fieldName The field name of the aggregate.
     * @return string|null The DQL expression for combining the aggregate.
     * Returns null if not applicable.
     */
    public function getAggregateToAggregateDQLExpression(
        string $fieldName,
    ): ?string;

    /**
     * Gets the DQL expression to convert an aggregate value into a value for
     * human consumption.
     *
     * @param string $inputExpression The DQL expression that gives the
     * aggregate value. The value is usually coming from the
     * `getAggregateToAggregateDQLExpression()` method, but not necessarily. The
     * implmementation should ignore the input expression if the measure is
     * virtual.
     */
    public function getAggregateToResultDQLExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
