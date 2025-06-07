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

/**
 * REKALOGIKA_HLL_HASH
 */
final class HllHashFunction extends FunctionNode
{
    private const HASH_TYPES = [
        'boolean',
        'smallint',
        'integer',
        'bigint',
        'bytea',
        'text',
        'any',
    ];

    public null|Node|string $argument = null;
    public null|Node|string $type = null;


    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        if (!$this->type instanceof Node) {
            $hashType = 'any';
        } else {
            $hashType = strtolower($this->type->dispatch($sqlWalker));
            $hashType = trim($hashType, "'\"");
        }

        if (!\in_array($hashType, self::HASH_TYPES, true)) {
            throw new QueryException('Unsupported type for hll_hash_* function: ' . $hashType);
        }

        $functionName = 'hll_hash_' . $hashType;

        if (!$this->argument instanceof Node) {
            throw new QueryException('Argument mus be a Node');
        }

        return \sprintf(
            '%s(%s)',
            $functionName,
            $this->argument->dispatch($sqlWalker),
        );
    }

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->argument = $parser->ArithmeticPrimary();

        if ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->type = $parser->Literal();
        } else {
            $this->type = 'any';
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
