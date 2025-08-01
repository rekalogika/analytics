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

namespace Rekalogika\Analytics\PostgreSQLExtra\AggregateFunction;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

final readonly class First implements SummarizableAggregateFunction
{
    private ValueResolver $property;

    public function __construct(ValueResolver $property)
    {
        $this->property = $property;
    }

    #[\Override]
    public function getSourceToAggregateExpression(SourceQueryContext $context): string
    {
        return \sprintf(
            "REKALOGIKA_FIRST(%s)",
            $this->property->getExpression($context),
        );
    }

    #[\Override]
    public function getAggregateToAggregateExpression(string $inputExpression): string
    {
        return \sprintf('REKALOGIKA_FIRST(%s)', $inputExpression);
    }

    #[\Override]
    public function getAggregateToResultExpression(
        string $inputExpression,
        SummaryQueryContext $context,
    ): string {
        return \sprintf('%s', $inputExpression);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }
}
