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
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @extends Bin<\DateTimeInterface>
 */
interface RecurringTimeBin extends TranslatableInterface, Bin
{
    public static function createFromDatabaseValue(int $databaseValue): static;
}
