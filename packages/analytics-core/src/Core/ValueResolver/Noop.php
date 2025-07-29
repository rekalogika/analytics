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
use Rekalogika\Analytics\Contracts\DimensionGroup\DimensionGroupAware;
use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Value resolver that does nothing.
 */
final readonly class Noop implements ValueResolver, DimensionGroupAware
{
    public function __construct(
        private ?ValueResolver $property = null,
    ) {}

    #[\Override]
    public function withInput(ValueResolver $input): static
    {
        return new self($input);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->property?->getInvolvedProperties() ?? [];
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        return $this->property?->getExpression($context)
            ?? throw new LogicException('No input resolver provided.');
    }
}
