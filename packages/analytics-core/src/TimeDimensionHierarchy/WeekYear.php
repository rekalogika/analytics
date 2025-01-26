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

final class WeekYear implements Interval
{
    use CacheTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setISODate($databaseValue, 1);

        $this->end = $this->start->setISODate($databaseValue + 1, 1);
    }

    public function getHierarchyLevel(): int
    {
        return 700;
    }

    #[\Override]
    public function getContainingIntervals(): array
    {
        return [];
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
        \DateTimeZone $timeZone,
    ): static {
        return new self(
            (int) $dateTime->format('o'),
            $timeZone,
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('o');
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
        return (int) $this->start->format('o');
    }

    public function getEndDatabaseValue(): int
    {
        return (int) $this->end->format('o');
    }
}
