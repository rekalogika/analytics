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
 * REKALOGIKA_BUST
 */
final class BustFunction extends FunctionNode
{
    public null|Node|string $input = null;
    public null|Node|string $random = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->input = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->random = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (!$this->input instanceof Node) {
            throw new QueryException('Expected Node, got ' . \gettype($this->input));
        }

        if (!$this->random instanceof Node) {
            throw new QueryException('Expected Node, got ' . \gettype($this->random));
        }

        return \sprintf(
            'REKALOGIKA_BUST(%s, %s)',
            $this->input->dispatch($sqlWalker),
            $this->random->dispatch($sqlWalker),
        );
    }
}
