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

namespace Rekalogika\Analytics\SummaryManager\Event;

final readonly class RefreshEndEvent
{
    private float $end;

    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
        private int|string|null $inputStartValue,
        private int|string|null $inputEndValue,
        private int|string|null $actualStartValue,
        private int|string|null $actualEndValue,
        private float $start,
    ) {
        $this->end = microtime(true);
    }

    public function getEventId(): string
    {
        return 'Refresh';
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getInputStartValue(): int|string|null
    {
        return $this->inputStartValue;
    }

    public function getInputEndValue(): int|string|null
    {
        return $this->inputEndValue;
    }

    public function getActualStartValue(): int|string|null
    {
        return $this->actualStartValue;
    }

    public function getActualEndValue(): int|string|null
    {
        return $this->actualEndValue;
    }

    public function getStart(): \DateTimeInterface
    {
        $result = \DateTimeImmutable::createFromFormat(
            'U.u',
            number_format($this->start, 6, '.', ''),
        );

        if (false === $result) {
            throw new \RuntimeException(\sprintf(
                'Failed to create DateTimeImmutable from %s.',
                (string) $this->start,
            ));
        }

        return $result;
    }

    public function getEnd(): \DateTimeInterface
    {
        $result = \DateTimeImmutable::createFromFormat(
            'U.u',
            number_format($this->end, 6, '.', ''),
        );

        if (false === $result) {
            throw new \RuntimeException(\sprintf(
                'Failed to create DateTimeImmutable from %s.',
                (string) $this->end,
            ));
        }

        return $result;
    }

    public function getDuration(): \DateInterval
    {
        return $this->getStart()->diff($this->getEnd());
    }
}
