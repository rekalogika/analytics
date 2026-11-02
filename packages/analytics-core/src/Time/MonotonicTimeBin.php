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

use Rekalogika\Analytics\Contracts\Model\DatabaseValueAware;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @extends DatabaseValueAware<int>
 */
interface MonotonicTimeBin extends
    \Stringable,
    TranslatableInterface,
    TimeBin,
    DatabaseValueAware
{
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static;

    /**
     * Equivalent to `createFromDatabaseValue($databaseValue)
     * ->withTimeZone($timeZone)`, without creating the intermediate UTC
     * instance.
     *
     * @internal
     */
    public static function createFromDatabaseValueAndTimeZone(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): static;

    public function withTimeZone(\DateTimeZone $timeZone): static;

    public function getStart(): \DateTimeInterface;

    public function getEnd(): \DateTimeInterface;
}
