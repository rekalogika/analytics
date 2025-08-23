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
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Time\Bin\Trait\RekalogikaTimeBinDQLExpressionTrait;
use Rekalogika\Analytics\Time\Bin\Trait\TimeBinTrait;
use Rekalogika\Analytics\Time\MonotonicTimeBin;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Year implements MonotonicTimeBin
{
    use TimeBinTrait;
    use RekalogikaTimeBinDQLExpressionTrait;

    public const TYPE = Types::SMALLINT;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $start;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private \DateTimeImmutable $end;

    #[\Override]
    private function initialize(): void
    {
        $string = \sprintf('%04d', $this->databaseValue);

        $y = (int) substr($string, 0, 4);

        $start = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            \sprintf('%04d-01-01 00:00:00', $y),
            $this->timeZone,
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
    private static function getSqlToCharArgument(): string
    {
        return 'YYYY';
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
