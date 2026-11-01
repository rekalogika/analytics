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

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

/**
 * An aggregate function whose partial results can be stored in the summary
 * table and combined later. The computation happens in three steps, each a
 * DQL expression:
 *
 * 1. Source to aggregate: the source entities of a partition are combined
 *    into one aggregate, stored in the summary table.
 * 2. Aggregate to aggregate: when a query spans several rows of the summary
 *    table, their aggregates are combined into one.
 * 3. Aggregate to result: the combined aggregate is converted into the final
 *    value (inherited from `AggregateFunction`).
 *
 * For a sum, all three steps are a `SUM()`. For a count, the first step is a
 * `COUNT()`, the second a `SUM()`, and the third the identity.
 */
interface SummarizableAggregateFunction extends AggregateFunction
{
    /**
     * Gets the DQL expression to combine multiple source values into an
     * aggregate.
     *
     * @param SourceQueryContext $context Resolves property paths of the source
     * entity into DQL.
     * @return string The DQL expression for transforming source values.
     */
    public function getSourceToAggregateExpression(
        SourceQueryContext $context,
    ): string;

    /**
     * Gets the DQL expression to combine, or roll-up data from multiple
     * aggregate fields into one larger aggregate.
     *
     * @param string $inputExpression The input expression of the aggregate
     * function. This is usually the field name.
     * @return string The DQL expression for combining the aggregate.
     */
    public function getAggregateToAggregateExpression(
        string $inputExpression,
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
