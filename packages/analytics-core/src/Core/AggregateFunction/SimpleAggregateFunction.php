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

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;

abstract readonly class SimpleAggregateFunction implements SummarizableAggregateFunction
{
    private ValueResolver $property;

    final public function __construct(
        string|ValueResolver $property,
    ) {
        if (\is_string($property)) {
            $property = new PropertyValue($property);
        }

        $this->property = $property;
    }

    abstract public function getAggregateFunction(string $input): string;

    #[\Override]
    public function getSourceToAggregateExpression(SourceQueryContext $context): string
    {
        return $this->getAggregateFunction($this->property->getExpression($context));
    }

    #[\Override]
    public function getAggregateToAggregateExpression(string $inputExpression): string
    {
        return $this->getAggregateFunction($inputExpression);
    }

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string {
        return $inputExpression;
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
