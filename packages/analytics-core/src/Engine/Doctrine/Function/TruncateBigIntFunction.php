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

namespace Rekalogika\Analytics\Engine\Doctrine\Function;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Common\Exception\QueryException;

/**
 * REKALOGIKA_TRUNCATE_BIGINT
 */
final class TruncateBigIntFunction extends FunctionNode
{
    public null|Node|string $variable = null;

    public null|Node|string $bits = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->variable = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->bits = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (!$this->variable instanceof Node) {
            throw new QueryException('Expected a Node');
        }

        if (!$this->bits instanceof Node) {
            throw new QueryException('Expected a Node');
        }

        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        $variable = $this->variable->dispatch($sqlWalker);
        $bits = $this->bits->dispatch($sqlWalker);

        return \sprintf(
            "(%s >> %s) << %s",
            $variable,
            $bits,
            $bits,
        );
    }
}
