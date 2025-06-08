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
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Contracts\Summary\SummaryContext;

final readonly class Range implements AggregateFunction
{
    public function __construct(
        private string $minProperty,
        private string $maxProperty,
    ) {}

    #[\Override]
    public function getSourceToAggregateDQLExpression(SourceContext $context): null
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
            '%s - %s',
            $context->getMeasureDQL($this->maxProperty),
            $context->getMeasureDQL($this->minProperty),
        );
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [];
    }
}
