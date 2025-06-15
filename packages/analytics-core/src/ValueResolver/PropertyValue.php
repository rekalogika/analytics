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

namespace Rekalogika\Analytics\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

final readonly class PropertyValue implements PartitionValueResolver
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
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be numeric, "%s" given.',
                get_debug_type($value),
            ));
        }

        return \intval($value);
    }

    #[\Override]
    public function transformSummaryValueToSourceValue(int $value): mixed
    {
        return $value;
    }
}
