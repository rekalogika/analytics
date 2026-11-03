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

namespace Rekalogika\Analytics\Time\Bin\MonthBasedWeek;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Contracts\Exception\BadMethodCallException;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Rekalogika\Analytics\Time\Util\DayBoundary;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Month based week-date (YYYYMMWD).
 *
 * A week starts on Monday and ends on Sunday. The first week of the month is
 * the week that contains the first Thursday of the month. The first week of the
 * month can extend into the previous month, and the last week of the month can
 * extend into the next month.
 */
final class MonthBasedWeekDate implements MonotonicTimeBin
{
    use TimeBinTrait;

    public const TYPE = Types::INTEGER;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $start;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $end;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private int $y;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private int $m;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private int $w;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private int $d;

    #[\Override]
    private function initialize(): void
    {
        $string = \sprintf('%08d', $this->databaseValue);

        $this->y = (int) substr($string, 0, 4); // Year
        $this->m = (int) substr($string, 4, 2); // Month
        $this->w = (int) substr($string, 6, 1); // Week of the month (1-5)
        $this->d = (int) substr($string, 7, 1); // Day of the week (1-7, where 1 is Monday)

        /** @var \DateTimeImmutable */
        $weekStart = DayBoundary::createCalendarDate()
            ->setDate($this->y, $this->m, 1) // Start from the first day of the month
            ->modify('first thursday of this month') // Adjust to the first Thursday
            ->modify('previous monday') // Move to the Monday of the week
            ->modify(\sprintf('+%d weeks', $this->w - 1)); // Move to the correct week

        // The arithmetic silently overflows an invalid month, week or day,
        // e.g. 20250251 into 2025-03-W1-1. The round trip rejects extra
        // digits, e.g. 202507121.
        if (
            $this->d < 1
            || $this->d > 7
            || !MonthBasedWeekWeek::isWeekInMonth($weekStart, $this->y, $this->m)
            || (int) \sprintf('%04d%02d%01d%01d', $this->y, $this->m, $this->w, $this->d) !== $this->databaseValue
        ) {
            throw new InvalidArgumentException(\sprintf('Invalid month based week date: %d', $this->databaseValue));
        }

        $date = $weekStart->modify(\sprintf('+%d days', $this->d - 1)); // Move to the correct day

        $this->start = DayBoundary::getStartOfDay($date, $this->timeZone);

        $this->end = DayBoundary::getStartOfDay(
            $date->modify('+1 day'),
            $this->timeZone,
        );
    }

    #[\Override]
    public static function getDQLExpression(
        string $sourceExpression,
        \DateTimeZone $sourceTimeZone,
        \DateTimeZone $summaryTimeZone,
    ): string {
        throw new BadMethodCallException('Not implemented yet.');
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        // Thursday of the ISO week containing the date. The week belongs to
        // the month of its Thursday.
        $thursdayThisWeek = \DateTimeImmutable::createFromInterface($dateTime)
            ->modify(\sprintf('%+d days', 4 - (int) $dateTime->format('N')));

        $thursdayDateOfMonth = (int) $thursdayThisWeek->format('d');
        $weekOfMonth = (int) ceil($thursdayDateOfMonth / 7);
        $month = (int) $thursdayThisWeek->format('m');
        $year = (int) $thursdayThisWeek->format('Y');
        $dayOfWeek = (int) $dateTime->format('N'); // 1 (Monday) to 7 (Sunday)

        $databaseValue = (int) \sprintf(
            '%04d%02d%01d%01d',
            $year,
            $month,
            $weekOfMonth,
            $dayOfWeek,
        );

        return self::create(
            $databaseValue,
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%04d-%02d-W%01d-%01d',
            $this->y,
            $this->m,
            $this->w,
            $this->d,
        );
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->__toString();
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

    #[\Override]
    public function getNext(): static
    {
        $nextDateTime = $this->start->modify('+1 day');

        return self::createFromDateTime($nextDateTime);
    }

    #[\Override]
    public function getPrevious(): static
    {
        $previousDateTime = $this->start->modify('-1 day');

        return self::createFromDateTime($previousDateTime);
    }
}
