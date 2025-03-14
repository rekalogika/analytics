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

interface Interval extends \Stringable, TranslatableInterface
{
    public static function createFromDatabaseValue(int $databaseValue): static;

    public function getDatabaseValue(): int;

    public function withTimeZone(\DateTimeZone $timeZone): static;

    public function getStart(): \DateTimeInterface;

    public function getEnd(): \DateTimeInterface;

    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static;

    // /**
    //  * Example: if this is a date, then this returns the week and month that
    //  * this date is in.
    //  *
    //  * @todo deprecate
    //  *
    //  * @return list<Interval>
    //  */
    // public function getContainingIntervals(): array;
}
