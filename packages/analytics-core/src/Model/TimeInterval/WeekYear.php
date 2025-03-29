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

use Rekalogika\Analytics\Contracts\Summary\TimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WeekYear implements TimeInterval
{
    use TimeIntervalTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $this->start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setISODate($databaseValue, 1)
            ->setTime(0, 0, 0);

        $this->end = $this->start->setISODate($databaseValue + 1, 1);
    }

    // #[\Override]
    // public function getContainingIntervals(): array
    // {
    //     return [];
    // }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('o'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('o');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
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

    // public function getStartDatabaseValue(): int
    // {
    //     return (int) $this->start->format('o');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('o');
    // }
}
