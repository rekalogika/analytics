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

namespace Rekalogika\Analytics\Core\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

/**
 * Resolve a property value as an integer.
 *
 * @implements PartitionValueResolver<int>
 */
final readonly class IntegerValue implements PartitionValueResolver
{
    public function __construct(
        private string $property,
    ) {}

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [$this->property];
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        return $context->resolve($this->property);
    }

    #[\Override]
    public function transformSourceValueToSummaryValue(mixed $value): int
    {
        return \intval($value);
    }

    #[\Override]
    public function transformSummaryValueToSourceValue(int $value): int
    {
        return $value;
    }
}
