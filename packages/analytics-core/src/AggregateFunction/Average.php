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

namespace Rekalogika\Analytics\AggregateFunction;

use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;

final readonly class Average implements AggregateFunction
{
    public function __construct(
        private string $sumProperty,
        private string $countProperty,
    ) {}

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string {
        return \sprintf(
            '%s / NULLIF(%s, 0)',
            $context->resolve($this->sumProperty),
            $context->resolve($this->countProperty),
        );
    }
}
