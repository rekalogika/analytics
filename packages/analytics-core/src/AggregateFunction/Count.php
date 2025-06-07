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

final readonly class Count extends SelfDecomposableAggregateFunction
{
    #[\Override]
    public function getDQLAggregateFunction(): string
    {
        return 'COUNT';
    }

    #[\Override]
    public function getAggregateToAggregateDQLExpression(string $field): string
    {
        return \sprintf('SUM(%s)', $field);
    }
}
