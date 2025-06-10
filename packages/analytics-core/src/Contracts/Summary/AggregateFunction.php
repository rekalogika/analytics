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
     * Gets the DQL expression to convert an aggregate value into a value for
     * human consumption.
     *
     * @param string $inputExpression The DQL expression that gives the
     * aggregate value. The framework will usually pass the output of
     * `getAggregateToAggregateDQLExpression()` method above, but not
     * necessarily. If this is not a summarizable aggregate function, the value
     * will be an empty string, and the implementation should ignore this
     * parameter.
     */
    public function getAggregateToResultDQLExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string;
}
