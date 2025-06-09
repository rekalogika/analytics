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

use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\SourceContext;
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
    public function getDQL(SourceContext $context): string
    {
        return $context->resolve($this->property);
    }

    #[\Override]
    public function transformSourceValueToSummaryValue(mixed $value): mixed
    {
        return $value;
    }

    #[\Override]
    public function transformSummaryValueToSourceValue(mixed $value): int|string
    {
        if (!\is_int($value) && !\is_string($value)) {
            throw new InvalidArgumentException(\sprintf(
                'Value must be an integer or string, "%s" given.',
                get_debug_type($value),
            ));
        }

        return $value;
    }
}
