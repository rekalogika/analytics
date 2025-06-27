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

namespace Rekalogika\Analytics\PostgreSQLHll\AggregateFunction;

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Context\SummaryQueryContext;
use Rekalogika\Analytics\Contracts\Context\ValueTransformerContext;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\UserValueTransformer;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\PostgreSQLHll\ApproximateCount;

/**
 * @implements UserValueTransformer<int,ApproximateCount>
 */
final readonly class CountDistinct implements
    SummarizableAggregateFunction,
    UserValueTransformer
{
    private ValueResolver $property;

    public function __construct(
        ValueResolver $property,
        private CountDistinctHashType $hashType = CountDistinctHashType::Any,
    ) {
        $this->property = $property;
    }

    #[\Override]
    public function getSourceToAggregateExpression(SourceQueryContext $context): string
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
        SummaryQueryContext $context,
    ): string {
        return \sprintf('REKALOGIKA_HLL_CARDINALITY(%s)', $inputExpression);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property->getInvolvedProperties();
    }

    #[\Override]
    public function getUserValue(
        mixed $rawValue,
        ValueTransformerContext $context,
    ): mixed {
        if ($rawValue === null) {
            return null;
        }

        if (!\is_int($rawValue)) {
            throw new InvalidArgumentException(\sprintf(
                'Expected an integer value, got "%s".',
                \gettype($rawValue),
            ));
        }

        return new ApproximateCount($rawValue);
    }
}
