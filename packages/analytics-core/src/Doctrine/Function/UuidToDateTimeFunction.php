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

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Exception\QueryException;

/**
 * REKALOGIKA_UUID_TO_DATETIME
 */
final class UuidToDateTimeFunction extends FunctionNode implements TypedExpression
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
        if (!$this->variable instanceof Node) {
            throw new QueryException('Expected a Node');
        }

        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        return \sprintf("TO_TIMESTAMP(('x'||LPAD(ENCODE(UUID_SEND(%s), 'hex'), 16, '0'))::bit(48)::bigint / 1000)", $this->variable->dispatch($sqlWalker));
    }

    #[\Override]
    public function getReturnType(): Type
    {
        return Type::getType(Types::DATETIME_IMMUTABLE);
    }
}
