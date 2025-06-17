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

namespace Rekalogika\Analytics\Time\Bin;

use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\TimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Month implements TimeBin
{
    use TimeBinTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%06d', $databaseValue);

        $y = (int) substr($string, 0, 4);
        $m = (int) substr($string, 4, 2);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            \sprintf('%04d-%02d-01 00:00:00', $y, $m),
            $timeZone,
        );

        if ($start === false) {
            throw new UnexpectedValueException(\sprintf(
                'Invalid date format: %s',
                \sprintf('%04d-%02d-01 00:00:00', $y, $m),
            ));
        }

        $this->start = $start;

        $this->end = $this->start
            ->modify('first day of next month')
            ->setTime(0, 0, 0);
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        $timeZone = $dateTime->getTimezone();

        return self::create(
            (int) $dateTime->format('Ym'),
            $timeZone,
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y-m');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $dateTime = new \DateTimeImmutable(
            $this->start->format('Y-m-d'),
            $this->start->getTimezone(),
        );

        try {
            $locale = $translator->getLocale();

            $intlDateFormatter = new \IntlDateFormatter(
                locale: $locale,
                dateType: \IntlDateFormatter::FULL,
                timeType: \IntlDateFormatter::FULL,
                timezone: $this->start->getTimezone(),
                pattern: 'MMMM YYYY',
            );

            $formatted = $intlDateFormatter->format($dateTime);

            if (!\is_string($formatted)) {
                return $this->getBasicString();
            }

            return $formatted;
        } catch (\Error) {
            return $this->getBasicString();
        }
    }

    private function getBasicString(): string
    {
        $month = match ($this->start->format('m')) {
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        };

        $year = $this->start->format('Y');

        return \sprintf('%s %s', $month, $year);
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
    //     return (int) $this->start->format('Ym');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('Ym');
    // }

    // private function getContainingQuarter(): Quarter
    // {
    //     $m = (int) $this->start->format('m');

    //     $q = match ($m) {
    //         1, 2, 3 => 1,
    //         4, 5, 6 => 2,
    //         7, 8, 9 => 3,
    //         10, 11, 12 => 4,
    //     };

    //     return Quarter::createFromDatabaseValue(
    //         (int) ($this->start->format('Y') . $q),
    //         $this->start->getTimezone(),
    //     );
    // }

    #[\Override]
    public function getNext(): static
    {
        return self::create(
            (int) $this->start->modify('first day of next month')->format('Ym'),
            $this->start->getTimezone(),
        );
    }

    #[\Override]
    public function getPrevious(): static
    {
        return self::create(
            (int) $this->start->modify('first day of last month')->format('Ym'),
            $this->start->getTimezone(),
        );
    }
}
