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

final readonly class SumSquare implements SummarizableAggregateFunction
{
    private ValueResolver $property;

    public function __construct(
        string|ValueResolver $property,
    ) {
        if (\is_string($property)) {
            $property = new PropertyValue($property);
        }

        $this->property = $property;
    }

    #[\Override]
    public function getSourceToAggregateExpression(SourceQueryContext $context): string
    {
        $expression = $this->property->getExpression($context);

        return \sprintf(
            'SUM(%s * %s)',
            $expression,
            $expression,
        );
    }

    #[\Override]
    public function getAggregateToAggregateExpression(
        string $inputExpression,
    ): string {
        return \sprintf('SUM(%s)', $inputExpression);
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
