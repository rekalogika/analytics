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
use Doctrine\ORM\QueryBuilder;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\SummaryManager\Query\Path\Path;
use Rekalogika\Analytics\SummaryManager\Query\Path\PathResolver;

final class QueryContext
{
    private ?PathResolver $pathResolver = null;
    private int $boundCounter = 0;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {}

    private function getPathResolver(): PathResolver
    {
        return $this->pathResolver ??= new PathResolver(
            rootClass: $this->getRootClass(),
            rootAlias: $this->getRootAlias(),
            queryBuilder: $this->queryBuilder,
        );
    }

    /**
     * @return class-string
     */
    private function getRootClass(): string
    {
        $result = $this->queryBuilder->getRootEntities()[0]
            ?? throw new LogicException('Root class not found');

        if (!class_exists($result)) {
            throw new LogicException('Root class not found');
        }

        return $result;
    }

    private function getRootAlias(): string
    {
        $alias = $this->queryBuilder->getRootAliases()[0]
            ?? throw new LogicException('Root alias not found');

        if ($alias !== 'root') {
            throw new LogicException('Root alias must be "root"');
        }

        return $alias;
    }

    /**
     * Path is a dot-separated string that represents a path to a property of an
     * entity. This method resolves the path to a DQL path, and joins the
     * necessary tables. If the path resolves to a related entity, you can
     * prefix the path with * to force a join, and return the alias.
     */
    public function resolvePath(string $path): string
    {
        return $this->getPathResolver()->resolvePath(new Path($path));
    }

    /**
     * Doctrine 2 does not have createNamedParameter method in QueryBuilder,
     * so we do it manually here.
     */
    public function createNamedParameter(
        mixed $value,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        $name = 'querycontext' . $this->boundCounter++;
        $this->queryBuilder->setParameter($name, $value, $type);

        return ':' . $name;
    }
}
