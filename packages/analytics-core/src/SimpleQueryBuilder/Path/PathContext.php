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

namespace Rekalogika\Analytics\SimpleQueryBuilder\Path;

use Doctrine\ORM\QueryBuilder;

final class PathContext
{
    private int $aliasCounter = 0;

    /**
     * @var array<string,string>
     */
    private array $baseToAlias = [];

    /**
     * @var array<string,BaseEntity>
     */
    private array $pathCache = [];

    /**
     * Returns a copy of this context whose base entities add their joins to
     * the given query builder.
     */
    public function withQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $context = new self();
        $context->aliasCounter = $this->aliasCounter;
        $context->baseToAlias = $this->baseToAlias;

        foreach ($this->pathCache as $baseEntity) {
            // the new base entity registers itself in the new context
            $baseEntity->withQueryBuilder($queryBuilder, $context);
        }

        return $context;
    }

    public function getAlias(string $basePath): string
    {
        if (isset($this->baseToAlias[$basePath])) {
            return $this->baseToAlias[$basePath];
        }

        $alias = '_a' . $this->aliasCounter++;

        return $this->baseToAlias[$basePath] = $alias;
    }

    public function getBaseEntityFromCache(string $basePath): ?BaseEntity
    {
        return $this->pathCache[$basePath] ?? null;
    }

    public function addBaseEntity(BaseEntity $path): void
    {
        $this->pathCache[$path->getBasePath()] = $path;
    }
}
