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

namespace Rekalogika\Analytics\Time\Model\TimeBin;

use Rekalogika\Analytics\Contracts\Model\TimeBin;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Year implements TimeBin
{
    use TimeBinTrait;

    private readonly \DateTimeImmutable $start;

    private readonly \DateTimeImmutable $end;

    private function __construct(
        int $databaseValue,
        \DateTimeZone $timeZone,
    ) {
        $this->databaseValue = $databaseValue;

        $string = \sprintf('%04d', $databaseValue);

        $y = (int) substr($string, 0, 4);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            \sprintf('%04d-01-01 00:00:00', $y),
            $timeZone,
        );

        if ($start === false) {
            throw new UnexpectedValueException(\sprintf(
                'Invalid date format: %s',
                \sprintf('%04d-01-01 00:00:00', $y),
            ));
        }

        $this->start = $start;

        $this->end = $this->start
            ->modify('first day of next year')
            ->setTime(0, 0, 0);
    }

    #[\Override]
    public static function createFromDateTime(
        \DateTimeInterface $dateTime,
    ): static {
        return self::create(
            (int) $dateTime->format('Y'),
            $dateTime->getTimezone(),
        );
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->start->format('Y');
    }

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
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

    // public function getStartDatabaseValue(): int
    // {
    //     return (int) $this->start->format('Y');
    // }

    // public function getEndDatabaseValue(): int
    // {
    //     return (int) $this->end->format('Y');
    // }

    #[\Override]
    public function getNext(): static
    {
        return self::create(
            $this->databaseValue + 1,
            $this->start->getTimezone(),
        );
    }

    #[\Override]
    public function getPrevious(): static
    {
        return self::create(
            $this->databaseValue - 1,
            $this->start->getTimezone(),
        );
    }
}
