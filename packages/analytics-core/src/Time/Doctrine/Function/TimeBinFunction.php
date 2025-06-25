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

namespace Rekalogika\Analytics\Time\Doctrine\Function;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rekalogika\Analytics\Common\Exception\QueryException;
use Rekalogika\Analytics\Time\TimeBinType;

/**
 * REKALOGIKA_TIME_BIN
 *
 * arguments:
 *
 * * source datetime
 * * stored time zone
 * * summary time zone
 * * output format (same as the values of TimeBinType enum)
 */
final class TimeBinFunction extends FunctionNode
{
    public null|Node|string $sourceDatetime = null;

    public null|Node|string $sourceTimeZone = null;

    public null|Node|string $summaryTimeZone = null;

    public null|Node|string $outputFormat = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->sourceDatetime = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->sourceTimeZone = $parser->Literal();
        $parser->match(TokenType::T_COMMA);
        $this->summaryTimeZone = $parser->Literal();
        $parser->match(TokenType::T_COMMA);
        $this->outputFormat = $parser->Literal();
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

        if (!$this->outputFormat instanceof Literal) {
            throw new QueryException('Output format must be a literal');
        }

        if (!$this->sourceDatetime instanceof Node) {
            throw new QueryException('Source datetime must be a node');
        }

        if (!$this->sourceTimeZone instanceof Literal) {
            throw new QueryException('Stored time zone must be a literal');
        }

        if (!$this->summaryTimeZone instanceof Literal) {
            throw new QueryException('Summary time zone must be a literal');
        }

        $value = $this->outputFormat->value;

        if (!\is_string($value)) {
            throw new QueryException(\sprintf(
                'Output format must be a string, got %s',
                get_debug_type($value),
            ));
        }

        $outputFormat = TimeBinType::tryFrom($value);

        if ($outputFormat === null) {
            throw new QueryException(\sprintf(
                'Unsupported output format "%s". Supported formats are: %s.',
                $value,
                implode(', ', array_map(
                    static fn(TimeBinType $type): string => $type->value,
                    TimeBinType::cases(),
                )),
            ));
        }

        return \sprintf(
            "REKALOGIKA_TIME_BIN(%s, %s, %s, '%s')",
            $this->sourceDatetime->dispatch($sqlWalker),
            $this->sourceTimeZone->dispatch($sqlWalker),
            $this->summaryTimeZone->dispatch($sqlWalker),
            $outputFormat->getSqlToCharArgument(),
        );
    }
}
