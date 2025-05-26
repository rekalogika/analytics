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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Rekalogika\Analytics\SimpleQueryBuilder\SimpleQueryBuilder;

final class QueryContext
{
    public function __construct(
        private readonly SimpleQueryBuilder $queryBuilder,
    ) {}

    /**
     * Path is a dot-separated string that represents a path to a property of an
     * entity. This method resolves the path to a DQL path, and joins the
     * necessary tables. If the path resolves to a related entity, you can
     * prefix the path with * to force a join, and return the alias.
     */
    public function resolvePath(string $path): string
    {
        return $this->queryBuilder->resolve($path);
    }

    /**
     * Doctrine 2 does not have createNamedParameter method in QueryBuilder,
     * so we do it manually here.
     */
    public function createNamedParameter(
        mixed $value,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        return $this->queryBuilder->createNamedParameter($value, $type);
    }
}
