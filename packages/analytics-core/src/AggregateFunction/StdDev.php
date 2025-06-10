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

use Rekalogika\Analytics\Contracts\Context\SummaryContext;
use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;

final readonly class StdDev implements AggregateFunction
{
    public function __construct(
        private string $sumSquareProperty,
        private string $countProperty,
        private string $sumProperty,
    ) {}

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string {
        return \sprintf(
            'SQRT((%s - (%s * %s / NULLIF(%s, 0))) / NULLIF(%s, 0))',
            $context->resolve($this->sumSquareProperty),
            $context->resolve($this->sumProperty),
            $context->resolve($this->sumProperty),
            $context->resolve($this->countProperty),
            $context->resolve($this->countProperty),
        );
    }
}
