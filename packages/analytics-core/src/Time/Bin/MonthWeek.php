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

namespace Rekalogika\Analytics\Time\Bin;

use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\TimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Month based week (YYYYMMW). A week starts on Monday and ends on Sunday. The
 * first week of the month is the week that contains the first Thursday of the
 * month. The first week of the month can extend into the previous month, and
 * the last week of the month can extend into the next month.
 */
final class MonthWeek implements TimeBin
{
    use TimeBinTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%07d', $databaseValue);

        $y = (int) substr($string, 0, 4); // Year
        $m = (int) substr($string, 4, 2); // Month
        $w = (int) substr($string, 6, 1); // Week of the month (1-5)

        /** @var \DateTimeImmutable */
        $start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setDate($y, $m, 1) // Start from the first day of the month
            ->setTime(0, 0, 0)
            ->modify('first thursday of this month') // Adjust to the first thursday
            ->modify('previous monday') // Move to the Monday of the week
            ->modify(\sprintf('+%d weeks', $w - 1)); // Move to the correct week

        /** @var \DateTimeImmutable */
        $end = $this->start->modify('+1 week');

        $this->start = $start;
        $this->end = $end;
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        $thursdayThisWeek = \DateTimeImmutable::createFromInterface($dateTime)
            ->modify('this thursday');

        $thursdayDateOfMonth = (int) $thursdayThisWeek->format('d');
        $weekOfMonth = (int) ceil($thursdayDateOfMonth / 7);
        $month = (int) $thursdayThisWeek->format('m');
        $year = (int) $thursdayThisWeek->format('Y');

        $databaseValue = (int) \sprintf(
            '%04d%02d%01d',
            $year,
            $month,
            $weekOfMonth,
        );

        return self::create(
            $databaseValue,
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('o-\WW');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $formatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::MEDIUM,
            timeType: \IntlDateFormatter::NONE,
            timezone: $this->start->getTimezone(),
        );

        return \sprintf(
            '%s - %s',
            $formatter->format($this->start),
            $formatter->format($this->end->modify('-1 day')),
        );
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
    //     return (int) $this->start->format('oW');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('oW');
    // }

    // private function getContainingWeekYear(): WeekYear
    // {
    //     return WeekYear::createFromDatabaseValue(
    //         (int) $this->start->format('o'),
    //         $this->start->getTimezone(),
    //     );
    // }

    #[\Override]
    public function getNext(): static
    {
        return self::create(
            (int) $this->end->format('oW'),
            $this->end->getTimezone(),
        );
    }

    #[\Override]
    public function getPrevious(): static
    {
        $previousWeek = $this->start->modify('-1 week');

        return self::create(
            (int) $previousWeek->format('oW'),
            $previousWeek->getTimezone(),
        );
    }
}
