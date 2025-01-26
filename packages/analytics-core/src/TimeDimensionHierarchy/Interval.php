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

interface Interval extends \Stringable
{
    public static function createFromDatabaseValue(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ): static;

    public function getStart(): \DateTimeInterface;

    public function getEnd(): \DateTimeInterface;

    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
        \DateTimeZone $timeZone,
    ): static;

    /**
     * Example: if this is a date, then this returns the week and month that
     * this date is in.
     *
     * @return list<Interval>
     */
    public function getContainingIntervals(): array;
}
