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

use Rekalogika\Analytics\RecurringTimeInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MonthOfYear implements RecurringTimeInterval
{
    use TimeIntervalTrait;

    private function __construct(
        private int $databaseValue,
        private \DateTimeZone $timeZone,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%02d', $this->databaseValue);
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $month = match ($this->databaseValue) {
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
            default => throw new \InvalidArgumentException(
                \sprintf('Invalid month of year: %d', $this->databaseValue),
            ),
        };

        $dateTime = (new \DateTimeImmutable('now', $this->timeZone))
            ->setDate(2000, $this->databaseValue, 1);

        $locale = $translator->getLocale();

        $intlDateFormatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            $this->timeZone,
            \IntlDateFormatter::GREGORIAN,
            'MMMM',
        );

        $formatted = $intlDateFormatter->format($dateTime);

        if (!\is_string($formatted)) {
            $formatted = $month;
        }

        return $formatted;
    }
}
