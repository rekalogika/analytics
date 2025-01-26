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

use Rekalogika\Analytics\SummaryManager\PartitionRange;

abstract readonly class AbstractEndEvent implements \Stringable
{
    private float $end;

    /**
     * @param class-string $class
     */
    final public function __construct(
        private string $class,
        private PartitionRange $range,
        private float $start,
    ) {
        $this->end = microtime(true);
    }

    abstract public function getEventId(): string;

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getRange(): PartitionRange
    {
        return $this->range;
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
