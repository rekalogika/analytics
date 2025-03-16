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

namespace Rekalogika\Analytics\TimeInterval;

trait RecurringTimeIntervalEnumTrait
{
    private static function create(int $databaseValue): static
    {
        return static::from($databaseValue);
    }

    public static function createFromDatabaseValue(int $databaseValue): static
    {
        return self::create($databaseValue);
    }

    public function getDatabaseValue(): int
    {
        return $this->value;
    }
}
