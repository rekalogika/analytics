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

use Doctrine\ORM\QueryBuilder;

abstract class AbstractQuery
{
    private readonly QueryContext $queryContext;

    protected function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {
        $this->queryContext = new QueryContext($queryBuilder);
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    protected function getQueryContext(): QueryContext
    {
        return $this->queryContext;
    }

    protected function resolvePath(string $path): string
    {
        return $this->getQueryContext()->resolvePath($path);
    }
}
