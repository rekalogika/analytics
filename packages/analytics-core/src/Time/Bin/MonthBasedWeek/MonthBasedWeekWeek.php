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

namespace Rekalogika\Analytics\Time\Bin\MonthBasedWeek;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Contracts\Translation\TranslatableMessage;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\HasTitle;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Month based week (YYYYMMW). A week starts on Monday and ends on Sunday. The
 * first week of the month is the week that contains the first Thursday of the
 * month. The first week of the month can extend into the previous month, and
 * the last week of the month can extend into the next month.
 */
final class MonthBasedWeekWeek implements MonotonicTimeBin, HasTitle
{
    use TimeBinTrait;

    public const TYPE = Types::INTEGER;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private readonly int $y;
    private readonly int $m;
    private readonly int $w;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%07d', $databaseValue);

        $this->y = (int) substr($string, 0, 4); // Year
        $this->m = (int) substr($string, 4, 2); // Month
        $this->w = (int) substr($string, 6, 1); // Week of the month (1-5)

        /** @var \DateTimeImmutable */
        $start = (new \DateTimeImmutable())
            ->setTimezone($timeZone)
            ->setDate($this->y, $this->m, 1) // Start from the first day of the month
            ->setTime(0, 0, 0)
            ->modify('first thursday of this month') // Adjust to the first thursday
            ->modify('previous monday') // Move to the Monday of the week
            ->modify(\sprintf('+%d weeks', $this->w - 1)); // Move to the correct week

        /** @var \DateTimeImmutable */
        $end = $start->modify('+1 week');

        $this->start = $start;
        $this->end = $end;
    }

    #[\Override]
    public function getTitle(): TranslatableInterface
    {
        $dateFormatter = new \IntlDateFormatter(
            locale: 'en',
            dateType: \IntlDateFormatter::FULL,
            timeType: \IntlDateFormatter::NONE,
            timezone: $this->start->getTimezone(),
            pattern: 'D MMMM YYYY',
        );

        return new TranslatableMessage(
            'Week starting on {start}, ending on {end}',
            [
                '{start}' => $dateFormatter->format($this->start),
                '{end}' => $dateFormatter->format($this->end),
            ],
        );
    }

    #[\Override]
    public static function getDQLExpression(
        string $sourceExpression,
        \DateTimeZone $sourceTimeZone,
        \DateTimeZone $summaryTimeZone,
    ): string {
        return \sprintf(
            "REKALOGIKA_TIME_BIN_MBW_WEEK(%s, '%s', '%s')",
            $sourceExpression,
            $sourceTimeZone->getName(),
            $summaryTimeZone->getName(),
        );
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
        return \sprintf(
            '%04d-%02d-W%01d',
            $this->y,
            $this->m,
            $this->w,
        );
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        try {
            $monthDateTime = (new \DateTimeImmutable())
                ->setDate($this->y, $this->m, 1)
                ->setTime(0, 0, 0)
                ->setTimezone($this->start->getTimezone());

            $intlDateFormatter = new \IntlDateFormatter(
                locale: $locale,
                dateType: \IntlDateFormatter::FULL,
                timeType: \IntlDateFormatter::FULL,
                timezone: $this->start->getTimezone(),
                pattern: 'MMMM YYYY',
            );

            $month = $intlDateFormatter->format($monthDateTime);

            $translation = new TranslatableMessage('Week {week}, {month}', [
                '{week}' => $this->w,
                '{month}' => $month,
            ]);

            return $translation->trans($translator, $locale);
        } catch (\Error) {
            return $this->__toString();
        }
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


    #[\Override]
    public function getNext(): static
    {
        $nextWeek = $this->start->modify('+1 week');

        return self::createFromDateTime($nextWeek);
    }

    #[\Override]
    public function getPrevious(): static
    {
        $previousWeek = $this->start->modify('-1 week');

        return self::createFromDateTime($previousWeek);
    }
}
