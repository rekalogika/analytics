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

namespace Rekalogika\Analytics\Model\TimeInterval;

use Rekalogika\Analytics\Contracts\Model\TimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Date implements TimeInterval
{
    use TimeIntervalTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%08d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $m = (int) substr($string, 4, 2);
        $d = (int) substr($string, 6, 2);

        $this->start = new \DateTimeImmutable(
            \sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d),
            $timeZone,
        );

        $this->end = $this->start->modify('+1 day');
    }

    // #[\Override]
    // public function getContainingIntervals(): array
    // {
    //     return [
    //         $this->getContainingWeek(),
    //         $this->getContainingMonth(),
    //     ];
    // }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('Ymd'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y-m-d');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $locale = $locale ?? $translator->getLocale();

        $intlDateFormatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::MEDIUM,
            timeType: \IntlDateFormatter::NONE,
            timezone: $this->start->getTimezone(),
        );

        $formatted = $intlDateFormatter->format($this->start);

        if (!\is_string($formatted)) {
            $formatted = (string) $this;
        }

        return $formatted;
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
    //     return (int) $this->start->format('Ymd');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('Ymd');
    // }

    // private function getContainingWeek(): Week
    // {
    //     return Week::createFromDatabaseValue(
    //         (int) $this->start->format('oW'),
    //         $this->start->getTimezone(),
    //     );
    // }

    // private function getContainingMonth(): Month
    // {
    //     return Month::createFromDatabaseValue(
    //         (int) $this->start->format('Ym'),
    //         $this->start->getTimezone(),
    //     );
    // }
}
