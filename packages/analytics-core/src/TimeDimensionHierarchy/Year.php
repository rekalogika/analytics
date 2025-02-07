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

final class Year implements Interval
{
    use CacheTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $string = \sprintf('%04d', $databaseValue);

        $y = (int) substr($string, 0, 4);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            \sprintf('%04d-01-01 00:00:00', $y),
            $timeZone,
        );

        if ($start === false) {
            throw new \InvalidArgumentException('Invalid database value');
        }

        $this->start = $start;

        $this->end = $this->start
            ->modify('first day of next year')
            ->setTime(0, 0, 0);
    }

    public function getHierarchyLevel(): int
    {
        return 600;
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
            (int) $dateTime->format('Y'),
            $timeZone,
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y');
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
        return (int) $this->start->format('Y');
    }

    public function getEndDatabaseValue(): int
    {
        return (int) $this->end->format('Y');
    }
}
