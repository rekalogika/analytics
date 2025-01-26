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

namespace Rekalogika\Analytics\AggregateFunction;

use Rekalogika\Analytics\AggregateFunction;
use Rekalogika\Analytics\SummaryManager\Query\QueryContext;

final readonly class Max implements AggregateFunction
{
    public function __construct(
        private string $sourceProperty,
    ) {}

    #[\Override]
    public function getSourceToSummaryDQLFunction(QueryContext $context): string
    {
        return \sprintf('MAX(%s)', $context->resolvePath($this->sourceProperty));
    }

    #[\Override]
    public function getSummaryToSummaryDQLFunction(): string
    {
        return 'MAX(%s)';
    }

    #[\Override]
    public function getInvolvedProperties(): array
    {
        return [$this->sourceProperty];
    }
}
