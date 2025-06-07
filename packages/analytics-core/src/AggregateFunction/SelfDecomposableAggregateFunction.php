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
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;

abstract readonly class SelfDecomposableAggregateFunction implements AggregateFunction
{
    private ValueResolver $property;

    final public function __construct(
        string|ValueResolver $property,
    ) {
        if (\is_string($property)) {
            $property = new PropertyValueResolver($property);
        }

        $this->property = $property;
    }

    abstract public function getDQLAggregateFunction(): string;

    #[\Override]
    public function getSourceToAggregateDQLExpression(Context $context): string
    {
        return \sprintf(
            '%s(%s)',
            $this->getDQLAggregateFunction(),
            $this->property->getDQL($context),
        );
    }

    #[\Override]
    public function getAggregateToAggregateDQLExpression(): string
    {
        return $this->getDQLAggregateFunction() . '(%s)';
    }

    #[\Override]
    public function getAggregateToResultDQLExpression(SummaryContext $context): string
    {
        return '%s';
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
