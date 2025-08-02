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
 * Wraps the inner value resolver with a random noop function. This allows you
 * to have identical clauses in the group by and grouping clauses without
 * confusing the database.
 */
final readonly class Bust implements ValueResolver
{
    public static function create(
        ValueResolver $input,
    ): self {
        return new self($input);
    }

    private function __construct(
        private ValueResolver $input,
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
        $random = rand(1, 2147483647);

        return \sprintf(
            'REKALOGIKA_BUST(%s, %s)',
            $innerExpression,
            $random,
        );
    }
}
