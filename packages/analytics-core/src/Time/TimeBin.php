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

namespace Rekalogika\Analytics\Time;

use Rekalogika\Analytics\Contracts\Model\Bin;

/**
 * @extends Bin<\DateTimeInterface>
 */
interface TimeBin extends Bin
{
    public const TYPE = '__needs_to_be_overriden__';

    public static function createFromDatabaseValue(int $databaseValue): static;

    public static function getDQLExpression(
        string $sourceExpression,
        \DateTimeZone $sourceTimeZone,
        \DateTimeZone $summaryTimeZone,
    ): string;
}
