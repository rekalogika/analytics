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

use Rekalogika\Analytics\Contracts\Summary\SourceContext;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\SummaryContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValue;

final readonly class CountDistinct implements SummarizableAggregateFunction
{
    private ValueResolver $property;

    public function __construct(
        string|ValueResolver $property,
        private CountDistinctHashType $hashType = CountDistinctHashType::Any,
    ) {
        if (\is_string($property)) {
            $property = new PropertyValue($property);
        }

        $this->property = $property;
    }

    #[\Override]
    public function getSourceToAggregateExpression(SourceContext $context): string
    {
        return \sprintf(
            "REKALOGIKA_HLL_ADD_AGG(REKALOGIKA_HLL_HASH(%s, '%s'))",
            $this->property->getExpression($context),
            $this->hashType->value,
        );
    }

    #[\Override]
    public function getAggregateToAggregateExpression(string $inputExpression): string
    {
        return \sprintf('REKALOGIKA_HLL_UNION_AGG(%s)', $inputExpression);
    }

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryContext $context,
    ): string {
        return \sprintf('REKALOGIKA_HLL_CARDINALITY(%s)', $inputExpression);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
