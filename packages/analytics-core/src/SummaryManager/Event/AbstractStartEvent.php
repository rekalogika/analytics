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

abstract readonly class AbstractStartEvent implements \Stringable
{
    private float $start;

    /**
     * @param class-string $class
     */
    final public function __construct(
        private string $class,
        private PartitionRange $range,
    ) {
        $this->start = microtime(true);
    }

    abstract public function getEventId(): string;

    /**
     * @return class-string<AbstractEndEvent>
     */
    public static function getEndEventClass(): string
    {
        $class = str_replace('StartEvent', 'EndEvent', static::class);

        if (!is_a($class, AbstractEndEvent::class, true)) {
            throw new \RuntimeException('Invalid end event class.');
        }

        return $class;
    }

    public function createEndEvent(): AbstractEndEvent
    {
        return new (static::getEndEventClass())(
            class: $this->class,
            range: $this->range,
            start: $this->start,
        );
    }

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
}
