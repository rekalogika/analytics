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

namespace Rekalogika\Analytics\Time\Bin\Trait;

trait RekalogikaTimeBinDQLExpressionTrait
{
    /**
     * Returns TO_CHAR() output template string for the SQL expression
     *
     * @see https://www.postgresql.org/docs/current/functions-formatting.html
     */
    abstract private static function getSqlToCharArgument(): string;

    public static function getDQLExpression(
        string $sourceExpression,
        \DateTimeZone $sourceTimeZone,
        \DateTimeZone $summaryTimeZone,
    ): string {
        return \sprintf(
            "REKALOGIKA_TIME_BIN(%s, '%s', '%s', '%s')",
            $sourceExpression,
            $sourceTimeZone->getName(),
            $summaryTimeZone->getName(),
            self::getSqlToCharArgument(),
        );
    }
}
