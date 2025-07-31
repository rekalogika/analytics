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

namespace Rekalogika\Analytics\Core\Doctrine\Function;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * REKALOGIKA_IN
 *
 * The standard Doctrine ORM `IN` operator does not work in every cases. This
 * function should work in all cases.
 *
 * Usage: REKALOGIKA_IN(argument, option1, option2, option3, ...) = TRUE
 */
final class InFunction extends FunctionNode
{
    public null|Node|string $argument = null;

    /**
     * @var list<Node|string>
     */
    public array $matches = [];

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->argument = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);

        $this->matches[] = $parser->ArithmeticPrimary();

        while ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->matches[] = $parser->StringPrimary();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (!$this->argument instanceof Node) {
            throw new QueryException('Expected Node, got ' . \gettype($this->argument));
        }

        return \sprintf(
            '%s IN (%s)',
            $this->argument->dispatch($sqlWalker),
            implode(', ', array_map(
                static fn(Node|string $match): string => $match instanceof Node
                    ? $match->dispatch($sqlWalker)
                    : $match,
                $this->matches,
            )),
        );
    }
}
