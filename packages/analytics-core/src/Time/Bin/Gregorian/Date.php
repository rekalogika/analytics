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

namespace Rekalogika\Analytics\Time\Bin\Gregorian;

use Doctrine\DBAL\Types\Types;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gregorian date (YYYYMMDD)
 */
final class Date implements MonotonicTimeBin
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
        $string = \sprintf('%08d', $this->databaseValue);

        $y = (int) substr($string, 0, 4);
        $m = (int) substr($string, 4, 2);
        $d = (int) substr($string, 6, 2);

        $this->start = new \DateTimeImmutable(
            \sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d),
            $this->timeZone,
        );

        $this->end = $this->start->modify('+1 day');
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('Ymd'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y-m-d');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $locale ??= $translator->getLocale();

        $intlDateFormatter = new \IntlDateFormatter(
            locale: $locale,
            dateType: \IntlDateFormatter::MEDIUM,
            timeType: \IntlDateFormatter::NONE,
            timezone: $this->start->getTimezone(),
        );

        $formatted = $intlDateFormatter->format($this->start);

        if (!\is_string($formatted)) {
            $formatted = (string) $this;
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

    #[\Override]
    public function getNext(): static
    {
        $next = $this->start->modify('+1 day');

        return self::create(
            (int) $next->format('Ymd'),
            $this->start->getTimezone(),
        );
    }

    #[\Override]
    public function getPrevious(): static
    {
        $previous = $this->start->modify('-1 day');

        return self::create(
            (int) $previous->format('Ymd'),
            $this->start->getTimezone(),
        );
    }

    #[\Override]
    private static function getSqlToCharArgument(): string
    {
        return 'YYYYMMDD';
    }
}
