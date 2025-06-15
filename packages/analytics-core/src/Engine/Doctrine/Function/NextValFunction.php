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
use Rekalogika\Analytics\Core\Exception\QueryException;

/**
 * REKALOGIKA_NEXTVAL
 *
 * arguments:
 *
 * * source datetime
 * * stored time zone
 * * summary time zone
 * * output format (same as the values of TimeGranularity)
 */
final class NextValFunction extends FunctionNode
{
    public null|Node|string $class = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->class = $parser->AbstractSchemaName();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new QueryException('Only supported on PostgreSQL for now');
        }

        // type checkings

        if (!\is_string($this->class)) {
            throw new QueryException('Expected string, got ' . \gettype($this->class));
        }

        if (!class_exists($this->class)) {
            throw new QueryException('Class ' . $this->class . ' does not exist');
        }

        $classMetadata = $sqlWalker->getEntityManager()->getClassMetadata($this->class);
        $sequence = $classMetadata->getSequenceName($platform);
        $sequenceSelect = $platform->getSequenceNextValSQL($sequence);

        // remove SELECT from the statement, so we can use it as a function
        $sequenceFunction = str_replace('SELECT ', '', $sequenceSelect);

        return $sequenceFunction;
    }
}
