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

namespace Rekalogika\Analytics\Doctrine\HyperLogLog\Function;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

abstract class AbstractSimpleFunction extends FunctionNode
{
    public null|Node|string $argument = null;

    #[\Override]
    final public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (!$this->argument instanceof Node) {
            throw new QueryException('Argument mus be a Node');
        }

        return \sprintf(
            '%s(%s)',
            $this->getFunctionName(),
            $this->argument->dispatch($sqlWalker),
        );
    }

    #[\Override]
    final public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->argument = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    abstract public function getFunctionName(): string;
}
