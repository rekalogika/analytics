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

namespace Rekalogika\Analytics\Doctrine\Function;

use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Exception\QueryException;

/**
 * REKALOGIKA_CAST
 */
final class CastFunction extends FunctionNode
{
    private ?ArithmeticExpression $expr1 = null;

    private ?Node $expr2 = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->expr1 = $parser->ArithmeticExpression();
        $parser->match(TokenType::T_COMMA);
        $this->expr2 = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        if ($this->expr1 === null || $this->expr2 === null) {
            throw new QueryException('CAST() function must have 2 arguments');
        }

        $type = trim($this->expr2->dispatch($sqlWalker), "'");

        return \sprintf(
            'CAST(%s AS %s)',
            $this->expr1->dispatch($sqlWalker),
            $type,
        );
    }
}
