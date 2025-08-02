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

namespace Rekalogika\Analytics\SimpleQueryBuilder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Contracts\Exception\BadMethodCallException;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\SimpleQueryBuilder\Path\PathResolver;

/**
 * @mixin QueryBuilder
 */
final class SimpleQueryBuilder
{
    private ?PathResolver $pathResolver = null;

    private ?QueryBuilder $queryBuilder = null;

    private int $boundCounter = 0;

    /**
     * @param class-string $from
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $from,
        private string $alias = 'root',
        private ?string $indexBy = null,
    ) {}

    public function __clone()
    {
        $this->queryBuilder = null;
        $this->pathResolver = null;
        $this->boundCounter = 0;
    }

    private function getPathResolver(): PathResolver
    {
        return $this->pathResolver ??= new PathResolver(
            baseClass: $this->from,
            baseAlias: $this->alias,
            queryBuilder: $this->getQueryBuilder(),
        );
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder ??= $this->entityManager
            ->createQueryBuilder()
            ->from($this->from, $this->alias, $this->indexBy);
    }

    public static function createFromRegistry(
        ManagerRegistry $managerRegistry,
        string $from,
        string $alias = 'root',
        ?string $indexBy = null,
    ): self {
        if (!class_exists($from)) {
            throw new InvalidArgumentException(\sprintf('Class "%s" does not exist', $from));
        }

        $manager = $managerRegistry->getManagerForClass($from);

        if (!$manager instanceof EntityManagerInterface) {
            throw new InvalidArgumentException(\sprintf(
                'No EntityManager found for class "%s". Ensure the class is managed by Doctrine ORM.',
                $from,
            ));
        }

        return new self(
            entityManager: $manager,
            from: $from,
            alias: $alias,
            indexBy: $indexBy,
        );
    }

    public function __call(mixed $name, mixed $arguments): mixed
    {
        if (!\is_string($name)) {
            throw new BadMethodCallException(\sprintf('Method name must be a string, %s given', \gettype($name)));
        }

        if (!method_exists($this->getQueryBuilder(), $name)) {
            throw new BadMethodCallException(\sprintf('Method %s does not exist on QueryBuilder', $name));
        }

        return $this->queryBuilder->{$name}(...$arguments);
    }

    public function getQueryComponents(): QueryComponents
    {
        return new QueryComponents($this->getQueryBuilder()->getQuery());
    }

    public function from(): self
    {
        throw new BadMethodCallException('The "from" method is not supported in SimpleQueryBuilder.');
    }

    /**
     * Path is a dot-separated string that represents a path to a property of an
     * entity. This method resolves the path to a DQL path, and joins the
     * necessary tables. If the path resolves to a related entity, you can
     * prefix the path with * to force a join, and return the alias.
     */
    public function resolve(string $path): string
    {
        return $this->getPathResolver()->resolve($path);
    }

    /**
     * Doctrine 2 does not have createNamedParameter method in QueryBuilder,
     * so we do it manually here.
     */
    public function createNamedParameter(
        mixed $value,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        $name = 'boundparameter' . $this->boundCounter++;

        // @todo Doctrine workaround. make sure this is correct
        if ($value instanceof \UnitEnum) {
            $type = null;
        }

        $this->getQueryBuilder()->setParameter($name, $value, $type);

        return ':' . $name;
    }

    /**
     * @param list<mixed> $values
     */
    public function createArrayNamedParameter(
        array $values,
        int|string|ParameterType|ArrayParameterType|null $type = null,
    ): string {
        $parameters = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            $parameters[] = $this->createNamedParameter($value, $type);
        }

        return implode(', ', $parameters);
    }
}
