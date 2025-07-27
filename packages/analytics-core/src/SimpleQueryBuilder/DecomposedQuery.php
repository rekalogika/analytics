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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

final readonly class DecomposedQuery
{
    /**
     * @param array<int<0,max>,mixed> $parameters
     * @param array<int<0,max>,int|string|ParameterType|ArrayParameterType> $types
     */
    private function __construct(
        private string $sql,
        private array $parameters,
        private array $types,
        private ResultSetMapping $resultSetMapping,
    ) {}

    public static function createFromQuery(Query $query): self
    {
        $components = new QueryComponents($query);

        return new self(
            sql: $components->getSqlStatement(),
            parameters: $components->getParameters(),
            types: $components->getTypes(),
            resultSetMapping: $components->getResultSetMapping(),
        );
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return array<int,mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<int,int|string|ParameterType|ArrayParameterType>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function execute(Connection $connection): int|string
    {
        /** @psalm-suppress InvalidArgument */
        return $connection->executeStatement(
            sql: $this->sql,
            params: $this->parameters,
            types: $this->types, // @phpstan-ignore argument.type
        );
    }

    public function prependSql(string $sql): self
    {
        return new self(
            sql: $sql . ' ' . $this->sql,
            parameters: $this->parameters,
            types: $this->types,
            resultSetMapping: $this->resultSetMapping,
        );
    }

    public function appendSql(string $sql): self
    {
        return new self(
            sql: $this->sql . ' ' . $sql,
            parameters: $this->parameters,
            types: $this->types,
            resultSetMapping: $this->resultSetMapping,
        );
    }

    public function withSql(string $sql): self
    {
        return new self(
            sql: $sql,
            parameters: $this->parameters,
            types: $this->types,
            resultSetMapping: $this->resultSetMapping,
        );
    }
}
