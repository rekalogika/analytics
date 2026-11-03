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

namespace Rekalogika\Analytics\Time\Util;

/**
 * @internal
 */
final readonly class DayBoundary
{
    private function __construct() {}

    /**
     * A date without a time of day and without DST transitions, for calendar
     * arithmetic. `setDate()` and `setISODate()` normalize an overflowing day
     * or week, e.g. 2025-01-32 into 2025-02-01.
     */
    public static function createCalendarDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@0');
    }

    /**
     * The start of the calendar day of `$date` in the given time zone: its
     * midnight, or the first existing time of the day if midnight falls in a
     * DST gap. Only the date of `$date` is used.
     *
     * Every day bin and week bin must compute both its start and its end with
     * this method, so that the end of a bin is the start of the next one. It
     * does not depend on the current time, unlike `setTime(0, 0)`, which
     * resolves an ambiguous midnight using the offset the object already has.
     */
    public static function getStartOfDay(
        \DateTimeInterface $date,
        \DateTimeZone $timeZone,
    ): \DateTimeImmutable {
        return new \DateTimeImmutable(
            $date->format('Y-m-d') . ' 00:00:00',
            $timeZone,
        );
    }
}
