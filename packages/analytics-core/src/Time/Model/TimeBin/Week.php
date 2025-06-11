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

namespace Rekalogika\Analytics\Time\Model\TimeBin;

use Rekalogika\Analytics\Time\TimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Week implements TimeBin
{
    use TimeBinTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%06d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $w = (int) substr($string, 4, 2);

        $this->start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setISODate($y, $w)
            ->setTime(0, 0, 0);

        $this->end = $this->start->modify('+1 week');
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('oW'),
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
