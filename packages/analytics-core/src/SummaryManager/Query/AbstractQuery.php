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

namespace Rekalogika\Analytics\SummaryManager\Query;

use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

abstract class AbstractQuery
{
    protected function __construct(
        private readonly SimpleQueryBuilder $simpleQueryBuilder,
    ) {}

    protected function getSimpleQueryBuilder(): SimpleQueryBuilder
    {
        return $this->simpleQueryBuilder;
    }

    protected function resolve(string $path): string
    {
        return $this->simpleQueryBuilder->resolve($path);
    }
}
