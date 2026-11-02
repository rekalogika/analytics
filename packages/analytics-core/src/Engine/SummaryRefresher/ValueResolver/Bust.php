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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\ValueResolver;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;

/**
 * Wraps the inner value resolver with a noop function tagged with an
 * identifier. This allows you to have identical clauses in the group by and
 * grouping clauses without confusing the database. The identifier must be
 * unique within a query, but should be deterministic so that the resulting SQL
 * is stable across runs.
 */
final readonly class Bust implements ValueResolver
{
    public static function create(
        ValueResolver $input,
        int $id,
    ): self {
        return new self($input, $id);
    }

    private function __construct(
        private ValueResolver $input,
        private int $id,
    ) {}

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return $this->input->getInvolvedProperties();
    }

    #[\Override]
    public function getExpression(SourceQueryContext $context): string
    {
        $innerExpression = $this->input->getExpression($context);

        return \sprintf(
            'REKALOGIKA_BUST(%s, %d)',
            $innerExpression,
            $this->id,
        );
    }
}
