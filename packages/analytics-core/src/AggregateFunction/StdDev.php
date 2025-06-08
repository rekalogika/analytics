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

final readonly class StdDev implements AggregateFunction
{
    public function __construct(
        private string $sumSquareProperty,
        private string $countProperty,
        private string $sumProperty,
    ) {}

    #[\Override]
    public function getSourceToAggregateDQLExpression(Context $context): null
    {
        return null;
    }

    #[\Override]
    public function getAggregateToAggregateDQLExpression(
        string $fieldName,
    ): null {
        return null;
    }

    #[\Override]
    public function getAggregateToResultDQLExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string {
        return \sprintf(
            'SQRT((%s - (%s * %s / NULLIF(%s, 0))) / NULLIF(%s, 0))',
            $context->getMeasureDQL($this->sumSquareProperty),
            $context->getMeasureDQL($this->sumProperty),
            $context->getMeasureDQL($this->sumProperty),
            $context->getMeasureDQL($this->countProperty),
            $context->getMeasureDQL($this->countProperty),
        );
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [];
    }
}
