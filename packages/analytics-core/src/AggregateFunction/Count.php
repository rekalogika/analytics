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

final readonly class Count extends SimpleAggregateFunction
{
    #[\Override]
    public function getAggregateFunction(string $input): string
    {
        return \sprintf('COUNT(%s)', $input);
    }

    #[\Override]
    public function getAggregateToAggregateExpression(
        string $inputExpression,
    ): string {
        return \sprintf('SUM(%s)', $inputExpression);
    }
}
