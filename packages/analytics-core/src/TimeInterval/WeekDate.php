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

namespace Rekalogika\Analytics\TimeInterval;

use Rekalogika\Analytics\TimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WeekDate implements TimeInterval
{
    use CacheTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%07d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $w = (int) substr($string, 4, 2);
        $d = (int) substr($string, 6, 1);

        $this->start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setISODate($y, $w, $d)
            ->setTime(0, 0, 0);

        $this->end = $this->start->modify('+1 day');
    }

    // #[\Override]
    // public function getContainingIntervals(): array
    // {
    //     return [
    //         $this->getContainingWeek(),
    //     ];
    // }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('oWN'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('o-\WW-N');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->start->format('o-\WW-N');
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
    //     return (int) $this->start->format('oWN');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('oWN');
    // }

    // private function getContainingWeek(): Week
    // {
    //     return Week::createFromDatabaseValue(
    //         (int) $this->start->format('oW'),
    //         $this->start->getTimezone(),
    //     );
    // }
}
