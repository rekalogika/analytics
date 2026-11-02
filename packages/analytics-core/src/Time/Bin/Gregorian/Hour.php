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

namespace Rekalogika\Analytics\Time\Bin\Gregorian;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Hour (YYYYMMDDHH)
 */
final class Hour implements MonotonicTimeBin
{
    use TimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::INTEGER;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $start;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $end;

    #[\Override]
    private function initialize(): void
    {
        $ymdh = \sprintf('%010d', $this->databaseValue);

        $y = (int) substr($ymdh, 0, 4);
        $m = (int) substr($ymdh, 4, 2);
        $d = (int) substr($ymdh, 6, 2);
        $h = (int) substr($ymdh, 8, 2);

        if (!checkdate($m, $d, $y) || $h > 23) {
            throw new InvalidArgumentException(\sprintf('Invalid hour: %d', $this->databaseValue));
        }

        $this->start = new \DateTimeImmutable(
            \sprintf('%04d-%02d-%02d %02d:00:00', $y, $m, $d, $h),
            $this->timeZone,
        );

        // In a DST spring-forward gap the wall-clock hour does not exist and
        // PHP normalizes it to the next existing hour, which is the start of
        // the following bin. Such a bin can never hold data, so make it empty
        // instead of letting it overlap the following bin.
        if ((int) $this->start->format('YmdH') !== $this->databaseValue) {
            $this->end = $this->start;

            return;
        }

        $this->end = self::getNextHourBoundary($this->start);
    }

    /**
     * Returns the first instant after the given one whose hour label
     * (YYYYMMDDHH in its time zone) differs from the given one's.
     *
     * The arithmetic is done on instants, not on the wall clock, so DST
     * transitions are handled: across a fall-back one label spans two
     * hours, and across a spring-forward one label is skipped. Wall-clock
     * arithmetic (`modify('-1 hour')`) would land on a non-existent hour
     * and be normalized back to where it started.
     */
    private static function getNextHourBoundary(
        \DateTimeImmutable $dateTime,
    ): \DateTimeImmutable {
        $label = $dateTime->format('YmdH');
        $result = $dateTime;

        do {
            $result = $result->setTimestamp($result->getTimestamp() + 3600);
        } while ($result->format('YmdH') === $label);

        return $result;
    }

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'YYYYMMDDHH24';
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('YmdH'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y-m-d H:00');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->start->format('Y-m-d H:00');
    }

    #[\Override]
    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    #[\Override]
    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    // public function getStartDatabaseValue(): int
    // {
    //     return (int) $this->start->format('YmdH');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('YmdH');
    // }

    // private function getContainingDate(): Date
    // {
    //     return Date::createFromDatabaseValue(
    //         (int) $this->start->format('Ymd'),
    //         $this->start->getTimezone(),
    //     );
    // }

    // private function getContainingWeekDate(): WeekDate
    // {
    //     return WeekDate::createFromDatabaseValue(
    //         (int) $this->start->format('oWw'),
    //         $this->start->getTimezone(),
    //     );
    // }

    #[\Override]
    public function getNext(): static
    {
        // $end is the first instant that belongs to the next bin. For an
        // empty bin (spring-forward gap) it equals $start, whose label is
        // already that of the next existing bin.
        return self::create(
            (int) $this->end->format('YmdH'),
            $this->timeZone,
        );
    }

    #[\Override]
    public function getPrevious(): static
    {
        // Subtract one hour of elapsed time; the instant one hour before the
        // start of this bin always lies inside the previous existing bin.
        $previous = $this->start->setTimestamp(
            $this->start->getTimestamp() - 3600,
        );

        return self::create(
            (int) $previous->format('YmdH'),
            $this->timeZone,
        );
    }
}
