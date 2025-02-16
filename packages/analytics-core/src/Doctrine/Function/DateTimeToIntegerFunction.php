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
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * REKALOGIKA_DATETIME_TO_INTEGER
 *
 * arguments:
 *
 * * source datetime
 * * stored time zone
 * * summary time zone
 * * output format (same as the values of TimeGranularity)
 */
final class DateTimeToIntegerFunction extends FunctionNode
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
            throw new \RuntimeException('Only supported on PostgreSQL for now');
        }

        // type checkings

        if (!$this->outputFormat instanceof Literal) {
            throw new \UnexpectedValueException('Output format must be a literal');
        }

        if (!$this->sourceDatetime instanceof Node) {
            throw new \UnexpectedValueException('Source datetime must be a node');
        }

        if (!$this->storedTimeZone instanceof Literal) {
            throw new \UnexpectedValueException('Stored time zone must be a literal');
        }

        if (!$this->summaryTimeZone instanceof Literal) {
            throw new \UnexpectedValueException('Summary time zone must be a literal');
        }

        $sqlOutputFormat = match ($this->outputFormat->value) {
            'hour' => 'YYYYMMDDHH24',
            'hourOfDay' => 'HH24',
            'date' => 'YYYYMMDD',
            'dayOfWeek' => 'D',
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
            default => throw new \RuntimeException('Invalid output format'),
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
