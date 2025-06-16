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
use Rekalogika\Analytics\Core\Exception\QueryException;

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

    public null|Node|string $storedTimeZone = null;

    public null|Node|string $summaryTimeZone = null;

    public null|Node|string $outputFormat = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->sourceDatetime = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->storedTimeZone = $parser->Literal();
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

        if (!$this->storedTimeZone instanceof Literal) {
            throw new QueryException('Stored time zone must be a literal');
        }

        if (!$this->summaryTimeZone instanceof Literal) {
            throw new QueryException('Summary time zone must be a literal');
        }

        if ($this->outputFormat->value === 'dayOfWeek') {
            // dayOfWeek is a special case, because TO_CHAR D outputs 1-7 sunday
            // to saturday, but we want ISO day of week 1-7 monday to sunday
            return 'EXTRACT(ISODOW FROM '
                . $this->sourceDatetime->dispatch($sqlWalker)
                . ' AT TIME ZONE '
                . $this->storedTimeZone->dispatch($sqlWalker)
                . ' AT TIME ZONE '
                . $this->summaryTimeZone->dispatch($sqlWalker)
                . ')::integer';
        }

        $sqlOutputFormat = match ($this->outputFormat->value) {
            'hour' => 'YYYYMMDDHH24',
            'hourOfDay' => 'HH24',
            'date' => 'YYYYMMDD',
            // 'dayOfWeek' => 'D',
            'dayOfMonth' => 'DD',
            'dayOfYear' => 'DDD',
            'week' => 'IYYYIW',
            'weekDate' => 'IYYYIWID',
            'weekYear' => 'IYYY',
            'weekOfYear' => 'IW',
            'weekOfMonth' => 'W',
            'month' => 'YYYYMM',
            'monthOfYear' => 'MM',
            'quarter' => 'YYYYQ',
            'quarterOfYear' => 'Q',
            'year' => 'YYYY',
            default => throw new QueryException(\sprintf(
                'Unsupported output format "%s". Supported formats are: hour, hourOfDay, date, dayOfWeek, dayOfMonth, dayOfYear, week, weekDate, weekYear, weekOfYear, weekOfMonth, month, monthOfYear, quarter, quarterOfYear and year.',
                get_debug_type($this->outputFormat->value),
            )),
        };

        return 'TO_CHAR('
            . $this->sourceDatetime->dispatch($sqlWalker)
            . ' AT TIME ZONE '
            . $this->storedTimeZone->dispatch($sqlWalker)
            . ' AT TIME ZONE '
            . $this->summaryTimeZone->dispatch($sqlWalker)
            . ', '
            . "'" . $sqlOutputFormat . "'"
            . ')::integer';
    }
}
