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

use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;

/**
 * Describes how the value of a measure is computed. Attached to a measure
 * property using the `Measure` attribute of the summary class.
 *
 * This base interface only covers the last step: turning the aggregate read
 * from the summary table into the final value of the query result. Most
 * functions also implement `SummarizableAggregateFunction`, which adds the
 * steps for building the aggregate from the source entities and for combining
 * aggregates.
 *
 * A function implementing only this interface is a derived measure that has
 * no column of its own in the summary table. It computes its value from other
 * measures using `SummaryQueryContext::resolve()`, e.g. an average computed
 * as a sum divided by a count.
 *
 * @see SummarizableAggregateFunction
 */
interface AggregateFunction
{
    /**
     * Gets the DQL expression to convert an aggregate value into the final
     * value.
     *
     * @param string $inputExpression The DQL expression that gives the
     * aggregate value. If the implementation is a
     * `SummarizableAggregateFunction`, the framework will usually pass the
     * output of `getAggregateToAggregateExpression()` method, but not
     * necessarily. If the implementation is not a
     * `SummarizableAggregateFunction`, the value will be an empty string, and
     * the implementation should ignore this parameter.
     * @param SummaryQueryContext $context Gives access to the expressions of
     * the other measures of the summary.
     * @return string The DQL expression producing the final value.
     */
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string;
}
