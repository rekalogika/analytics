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

namespace Rekalogika\Analytics\Engine\SummaryRefresher\Event;

use Rekalogika\Analytics\Engine\Util\DateTimeUtil;

final readonly class RefreshStartEvent
{
    private float $start;

    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
        private int|string|null $inputStartValue,
        private int|string|null $inputEndValue,
        private int|string|null $actualStartValue,
        private int|string|null $actualEndValue,
    ) {
        $this->start = microtime(true);
    }

    public function getEventId(): string
    {
        return 'Refresh';
    }

    public function createEndEvent(): RefreshEndEvent
    {
        return new RefreshEndEvent(
            class: $this->class,
            inputStartValue: $this->inputStartValue,
            inputEndValue: $this->inputEndValue,
            actualStartValue: $this->actualStartValue,
            actualEndValue: $this->actualEndValue,
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
        return DateTimeUtil::floatToDateTime($this->start);
    }
}
