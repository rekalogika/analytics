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

namespace Rekalogika\Analytics\Time\Bin\IsoWeek;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\HasTitle;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Rekalogika\Analytics\Time\Util\DayBoundary;
use Rekalogika\Analytics\Time\Util\TranslatableDate;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ISO 8601 week (YYYYWW)
 */
final class IsoWeekWeek implements MonotonicTimeBin, HasTitle
{
    use TimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::INTEGER;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $start;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $end;

    #[\Override]
    private function initialize(): void
    {
        $string = \sprintf('%06d', $this->databaseValue);

        $y = (int) substr($string, 0, 4);
        $w = (int) substr($string, 4, 2);

        $date = DayBoundary::createCalendarDate()->setISODate($y, $w);

        // `setISODate()` silently overflows an invalid week, e.g. 2021-W53
        // into 2022-W01
        if ((int) $date->format('oW') !== $this->databaseValue) {
            throw new InvalidArgumentException(\sprintf('Invalid ISO week: %d', $this->databaseValue));
        }

        $this->start = DayBoundary::getStartOfDay($date, $this->timeZone);

        $this->end = DayBoundary::getStartOfDay(
            $date->modify('+1 week'),
            $this->timeZone,
        );
    }

    #[\Override]
    public function getTitle(): TranslatableInterface
    {
        return new TranslatableMessage(
            'Week starting on {start}, ending on {end}',
            [
                '{start}' => new TranslatableDate($this->start),
                '{end}' => new TranslatableDate($this->end->modify('-1 day')),
            ],
        );
    }

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'IYYYIW';
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
        $locale ??= $translator->getLocale();

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
