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

interface AggregateFunction
{
    /**
     * Gets the DQL expression to convert an aggregate value into the final
     * value.
     *
     * @param string $inputExpression The DQL expression that gives the
     * aggregate value. If the implementation is a
     * `SummarizableAggregateFunction`,`the framework will usually pass the
     * output of `getAggregateToAggregateExpression()` method, but not
     * necessarily. If the implementationis not a
     * `SummarizableAggregateFunction`, the value will be an empty string, and
     * the implementation should ignore this parameter.
     */
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string;
}
