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

namespace Rekalogika\Analytics\Core\AggregateFunction;

final readonly class Max extends SimpleAggregateFunction
{
    #[\Override]
    public function getAggregateFunction(string $input): string
    {
        return \sprintf('MAX(%s)', $input);
    }
}
