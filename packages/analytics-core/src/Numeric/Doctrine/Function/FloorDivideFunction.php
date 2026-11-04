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

namespace Rekalogika\Analytics\Numeric\Doctrine\Function;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Contracts\Exception\QueryException;

/**
 * REKALOGIKA_FLOOR_DIV(dividend, divisor)
 *
 * Divides the dividend by the divisor, and rounds the result down to an
 * integer. Unlike the integer division of the database, it rounds towards
 * negative infinity, not towards zero, and works with decimals.
 */
final class FloorDivideFunction extends FunctionNode
{
    public null|Node|string $dividend = null;

    public null|Node|string $divisor = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->dividend = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->divisor = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (!$this->dividend instanceof Node || !$this->divisor instanceof Node) {
            throw new QueryException('Dividend and divisor must be nodes');
        }

        return \sprintf(
            'CAST(FLOOR(CAST((%s) AS NUMERIC) / CAST((%s) AS NUMERIC)) AS INTEGER)',
            $this->dividend->dispatch($sqlWalker),
            $this->divisor->dispatch($sqlWalker),
        );
    }
}
