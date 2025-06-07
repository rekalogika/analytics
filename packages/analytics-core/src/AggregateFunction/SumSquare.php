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

final readonly class SumSquare implements AggregateFunction
{
    private ValueResolver $property;

    public function __construct(
        string|ValueResolver $property,
    ) {
        if (\is_string($property)) {
            $property = new PropertyValueResolver($property);
        }

        $this->property = $property;
    }

    #[\Override]
    public function getSourceToAggregateDQLExpression(Context $context): string
    {
        $expression = $this->property->getDQL($context);

        return \sprintf(
            'SUM(%s * %s)',
            $expression,
            $expression,
        );
    }

    #[\Override]
    public function getAggregateToAggregateDQLExpression(
        string $fieldName,
    ): string {
        return \sprintf('SUM(%s)', $fieldName);
    }

    #[\Override]
    public function getAggregateToResultDQLExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string {
        return $inputExpression;
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
