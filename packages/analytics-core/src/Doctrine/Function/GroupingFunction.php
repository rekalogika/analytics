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

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Exception\QueryException;

/**
 * REKALOGIKA_GROUPING
 *
 * @deprecated
 */
final class GroupingFunction extends FunctionNode
{
    public null|Node|string $variable = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->variable = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        // type checkings

        if (!$this->variable instanceof Node) {
            throw new QueryException('Expected Node, got ' . get_debug_type($this->variable));
        }

        return \sprintf('GROUPING(%s) = 1', $this->variable->dispatch($sqlWalker));
    }
}
