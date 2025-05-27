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
