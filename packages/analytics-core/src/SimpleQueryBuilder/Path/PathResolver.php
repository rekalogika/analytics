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
use Rekalogika\Analytics\Core\Exception\LogicException;

final readonly class PathResolver
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

    public function resolve(string $path): string
    {
        $path = Path::createFromString($path);

        if (\count($path) === 0) {
            throw new LogicException('Path is empty, cannot resolve');
        }

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
