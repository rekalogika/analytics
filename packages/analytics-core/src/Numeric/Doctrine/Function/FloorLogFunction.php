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
 * REKALOGIKA_FLOOR_LOG(value, base, scale)
 *
 * The logarithm of `value / scale` to the given base, rounded down to an
 * integer. The value must be positive. The calculation is done in the NUMERIC
 * type, so that an exact power of the base gives an exact result, e.g. the
 * logarithm of 1000 to the base 10 is 3, and not 2.9999999999999996.
 */
final class FloorLogFunction extends FunctionNode
{
    public null|Node|string $value = null;

    public null|Node|string $base = null;

    public null|Node|string $scale = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->value = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->base = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->scale = $parser->SimpleArithmeticExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (
            !$this->value instanceof Node
            || !$this->base instanceof Node
            || !$this->scale instanceof Node
        ) {
            throw new QueryException('Value, base, and scale must be nodes');
        }

        return \sprintf(
            'CAST(FLOOR(LOG(CAST((%s) AS NUMERIC), CAST((%s) AS NUMERIC) / CAST((%s) AS NUMERIC))) AS INTEGER)',
            $this->base->dispatch($sqlWalker),
            $this->value->dispatch($sqlWalker),
            $this->scale->dispatch($sqlWalker),
        );
    }
}
