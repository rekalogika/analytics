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

namespace Rekalogika\Analytics\TimeDimensionHierarchy;

use Symfony\Contracts\Translation\TranslatableInterface;

interface RecurringInterval extends \Stringable, TranslatableInterface
{
    public static function createFromDatabaseValue(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): static;
}
