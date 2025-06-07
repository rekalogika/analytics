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

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\Context;
use Rekalogika\Analytics\Contracts\Summary\SummaryContext;

final readonly class Average implements AggregateFunction
{
    public function __construct(
        private string $sumProperty,
        private string $countProperty,
    ) {}

    #[\Override]
    public function getSourceToAggregateDQLExpression(Context $context): null
    {
        return null;
    }

    #[\Override]
    public function getAggregateToAggregateDQLExpression(string $field): null
    {
        return null;
    }

    #[\Override]
    public function getAggregateToResultDQLExpression(SummaryContext $context): string
    {
        return \sprintf(
            '%s / %s',
            $context->getMeasureDQL($this->sumProperty),
            $context->getMeasureDQL($this->countProperty),
        );
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [];
    }
}
