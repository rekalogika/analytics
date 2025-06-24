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
use Rekalogika\Analytics\Contracts\DimensionGroup\DimensionGroupAware;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Wraps the inner value resolver with a random noop function. This allows you
 * to have identical clauses in the group by and grouping clauses without
 * confusing the database.
 *
 * @todo replace with a custom function
 */
final readonly class Bust implements ValueResolver, DimensionGroupAware
{
    public function __construct(
        private ?ValueResolver $input = null,
        private DataType $dataType = DataType::Numeric,
    ) {}

    #[\Override]
    public function withInput(ValueResolver $input): static
    {
        return new self($input);
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->input?->getInvolvedProperties() ?? [];
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        $innerExpression = $this->input?->getExpression($context)
            ?? throw new LogicException('No input resolver provided.');

        $random = match ($this->dataType) {
            DataType::Text => \sprintf("'%s'", bin2hex(random_bytes(8))),
            DataType::Numeric => rand(1, PHP_INT_MAX),
            DataType::Timestamp => \sprintf("'%s'::timestamp", date('Y-m-d H:i:s', rand(1, PHP_INT_MAX))),
        };

        return \sprintf(
            'CASE WHEN %s = %s THEN %s ELSE NULLIF(%s, %s) END',
            $random,
            $random,
            $innerExpression,
            $random,
            $random,
        );
    }
}
