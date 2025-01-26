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

/**
 * REKALOGIKA_GROUPING_CONCAT
 */
class GroupingConcatFunction extends FunctionNode
{
    /**
     * @var list<Node>
     */
    public array $concatExpressions = [];

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        $args = [];

        foreach ($this->concatExpressions as $expression) {
            $args[] = 'GROUPING(' . $sqlWalker->walkStringPrimary($expression) . ')::text';
        }

        if ($args === []) {
            return "''";
        }

        if (\count($args) === 1) {
            return $args[0];
        }

        return $platform->getConcatExpression(...$args);
    }

    /** @inheritDoc */
    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        while (true) {
            if ($parser->getLexer()->isNextToken(TokenType::T_CLOSE_PARENTHESIS)) {
                $parser->match(TokenType::T_CLOSE_PARENTHESIS);
                break;
            } elseif ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
                $parser->match(TokenType::T_COMMA);
            } else {
                $this->concatExpressions[] = $parser->StringPrimary();
            }
        }
    }
}
