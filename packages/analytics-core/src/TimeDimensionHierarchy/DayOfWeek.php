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

use Symfony\Contracts\Translation\TranslatorInterface;

final class DayOfWeek implements RecurringInterval
{
    use CacheTrait;

    private function __construct(
        private int $databaseValue,
        private \DateTimeZone $timeZone,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->databaseValue;
    }

    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null
    ): string {
        $dayOfWeek = match ($this->databaseValue) {
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
            default => throw new \InvalidArgumentException(
                \sprintf('Invalid day of week: %d', $this->databaseValue),
            ),
        };

        $dateTime = new \DateTimeImmutable('next ' . $dayOfWeek, $this->timeZone);

        $locale = setlocale(LC_TIME, '0');

        if ($locale === false) {
            $locale = 'C';
        }

        $intlDateFormatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $this->timeZone,
            \IntlDateFormatter::GREGORIAN,
            'EEEE',
        );

        $formatted = $intlDateFormatter->format($dateTime);

        if (!\is_string($formatted)) {
            $formatted = $dayOfWeek;
        }

        return $formatted;
    }
}
