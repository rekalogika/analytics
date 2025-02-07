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

final class Quarter implements Interval
{
    use CacheTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $string = \sprintf('%05d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $q = (int) substr($string, 4, 1);

        $m = self::quarterToFirstMonth($q);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            \sprintf('%04d-%02d-01', $y, $m),
            $timeZone,
        );

        if ($start === false) {
            throw new \InvalidArgumentException('Invalid database value');
        }

        $this->start = $start->setTime(0, 0, 0);

        $this->end = $this->start
            ->setDate($y, $m + 3, 1)
            ->setTime(0, 0, 0);
    }

    public function getHierarchyLevel(): int
    {
        return 500;
    }

    #[\Override]
    public function getContainingIntervals(): array
    {
        return [
            $this->getContainingYear(),
        ];
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
        \DateTimeZone $timeZone,
    ): static {
        $q = self::monthToQuarter((int) $dateTime->format('m'));
        $v = (int) ($dateTime->format('Y') . $q);

        return new self($v, $timeZone);
    }

    #[\Override]
    public function __toString(): string
    {
        return
            \sprintf(
                '%s Q%d',
                $this->start->format('Y'),
                self::monthToQuarter((int) $this->start->format('m')),
            );
    }

    public static function monthToQuarter(int $month): int
    {
        return match ($month) {
            1, 2, 3 => 1,
            4, 5, 6 => 2,
            7, 8, 9 => 3,
            10, 11, 12 => 4,
            default => throw new \InvalidArgumentException('Invalid month: ' . $month),
        };
    }

    public static function quarterToFirstMonth(int $quarter): int
    {
        return match ($quarter) {
            1 => 1,
            2 => 4,
            3 => 7,
            4 => 10,
            default => throw new \InvalidArgumentException('Invalid quarter: ' . $quarter),
        };
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
        $q = self::monthToQuarter((int) $this->start->format('m'));

        return (int) ($this->start->format('Y') . $q);
    }

    public function getEndDatabaseValue(): int
    {
        $q = self::monthToQuarter((int) $this->end->format('m'));

        return (int) ($this->end->format('Y') . $q);
    }

    private function getContainingYear(): Year
    {
        return Year::createFromDatabaseValue(
            (int) $this->start->format('Y'),
            $this->start->getTimezone(),
        );
    }
}
