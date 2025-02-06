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

use Webmozart\Assert\Assert;

final class Month implements Interval
{
    use CacheTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $string = \sprintf('%06d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $m = (int) substr($string, 4, 2);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            \sprintf('%04d-%02d-01 00:00:00', $y, $m),
            $timeZone,
        );
        Assert::isInstanceOf($start, \DateTimeImmutable::class);
        $this->start = $start;

        $this->end = $this->start
            ->modify('first day of next month')
            ->setTime(0, 0, 0);
    }

    public function getHierarchyLevel(): int
    {
        return 400;
    }

    #[\Override]
    public function getContainingIntervals(): array
    {
        return [
            $this->getContainingQuarter(),
        ];
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
        \DateTimeZone $timeZone,
    ): static {
        return new self(
            (int) $dateTime->format('Ym'),
            $timeZone,
        );
    }

    #[\Override]
    public function __toString(): string
    {
        $month = match ($this->start->format('m')) {
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        };

        $dateTime = new \DateTimeImmutable(
            $this->start->format('Y-m-d'),
            $this->start->getTimezone(),
        );

        $locale = setlocale(LC_TIME, '0');

        if ($locale === false) {
            $locale = 'C';
        }

        $intlDateFormatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $this->start->getTimezone(),
            \IntlDateFormatter::GREGORIAN,
            'MMMM YYYY',
        );

        $formatted = $intlDateFormatter->format($dateTime);

        if (!\is_string($formatted)) {
            $formatted = $month;
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

    public function getStartDatabaseValue(): int
    {
        return (int) $this->start->format('Ym');
    }

    public function getEndDatabaseValue(): int
    {
        return (int) $this->end->format('Ym');
    }

    private function getContainingQuarter(): Quarter
    {
        $m = (int) $this->start->format('m');

        $q = match ($m) {
            1, 2, 3 => 1,
            4, 5, 6 => 2,
            7, 8, 9 => 3,
            10, 11, 12 => 4,
        };

        return Quarter::createFromDatabaseValue(
            (int) ($this->start->format('Y') . $q),
            $this->start->getTimezone(),
        );
    }
}
