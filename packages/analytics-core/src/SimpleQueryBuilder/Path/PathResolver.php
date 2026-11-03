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
use Rekalogika\Analytics\Contracts\Exception\LogicException;

final class PathResolver
{
    private PathContext $context;

    private BaseEntity $baseEntity;

    /**
     * @param class-string $baseClass
     */
    public function __construct(
        string $baseClass,
        string $baseAlias,
        private QueryBuilder $queryBuilder,
    ) {
        $this->context = new PathContext();

        $this->baseEntity = new BaseEntity(
            basePath: '',
            baseClass: $baseClass,
            baseAlias: $baseAlias,
            queryBuilder: $queryBuilder,
            context: $this->context,
        );
    }

    /**
     * Returns a copy of this resolver that adds its joins to the given query
     * builder, which must be a clone of the current one. The joins already
     * made are kept, under the same aliases.
     */
    public function withQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $context = $this->context->withQueryBuilder($queryBuilder);

        $clone = clone $this;
        $clone->queryBuilder = $queryBuilder;
        $clone->context = $context;
        $clone->baseEntity = $context->getBaseEntityFromCache($this->baseEntity->getBasePath())
            ?? throw new LogicException('Base entity is not in the context');

        return $clone;
    }

    public function resolve(string $path): string
    {
        $path = Path::createFromString($path);

        return $this->baseEntity->resolve($path);
    }

    /**
     * @internal
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @internal
     */
    public function getContext(): PathContext
    {
        return $this->context;
    }
}
