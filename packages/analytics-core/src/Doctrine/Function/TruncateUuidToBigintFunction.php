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

/**
 * REKALOGIKA_TRUNCATE_UUID_TO_BIGINT
 */
class TruncateUuidToBigintFunction extends FunctionNode implements TypedExpression
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
            throw new \RuntimeException('Expected a Node');
        }


        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new \RuntimeException('Only supported on PostgreSQL for now');
        }

        return \sprintf(
            "(('x'||ENCODE(UUID_SEND(%s::uuid), 'hex'))::bit(48))::bigint",
            $this->variable->dispatch($sqlWalker),
        );
    }

    #[\Override]
    public function getReturnType(): Type
    {
        return Type::getType(Types::DATETIME_IMMUTABLE);
    }
}
