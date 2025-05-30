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

/**
 * @deprecated
 */
final class QueryContext
{
    public function __construct(
        private readonly SimpleQueryBuilder $queryBuilder,
    ) {}

    public function resolvePath(string $path): string
    {
        return $this->queryBuilder->resolve($path);
    }


}
