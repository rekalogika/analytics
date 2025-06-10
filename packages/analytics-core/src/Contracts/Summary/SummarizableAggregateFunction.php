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

use Rekalogika\Analytics\Contracts\Context\SourceContext;

interface SummarizableAggregateFunction extends AggregateFunction
{
    /**
     * Gets the DQL expression to combine multiple source values into an
     * aggregate.
     *
     * @return string The DQL expression for transforming source values.
     */
    public function getSourceToAggregateExpression(
        SourceContext $context,
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
