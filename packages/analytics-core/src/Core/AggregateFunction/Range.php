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

namespace Rekalogika\Analytics\Core\AggregateFunction;

use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;

final readonly class Range implements AggregateFunction
{
    public function __construct(
        private string $minProperty,
        private string $maxProperty,
    ) {}

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string {
        return \sprintf(
            '%s - %s',
            $context->resolve($this->maxProperty),
            $context->resolve($this->minProperty),
        );
    }
}
