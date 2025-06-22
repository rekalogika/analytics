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

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Hierarchy\HierarchyAware;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Wraps the inner value resolver with a random noop function. This allows you
 * to have identical clauses in the group by and grouping clauses without
 * confusing the database.
 */
final readonly class Smash implements ValueResolver, HierarchyAware
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
        $innerExpression = $this->property?->getExpression($context)
            ?? throw new LogicException('No input resolver provided.');

        $random = rand(1, PHP_INT_MAX);

        return \sprintf(
            'CASE WHEN %s = %s THEN %s END',
            $random,
            $random,
            $innerExpression,
        );
    }
}
