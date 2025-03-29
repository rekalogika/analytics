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
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

final readonly class PropertyValueResolver implements PartitionValueResolver
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
    public function getDQL(QueryContext $context): string
    {
        return $context->resolvePath($this->property);
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
            throw new \InvalidArgumentException('Value must be an integer or a string');
        }

        return $value;
    }
}
