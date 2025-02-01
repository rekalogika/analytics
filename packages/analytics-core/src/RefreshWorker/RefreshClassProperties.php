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

namespace Rekalogika\Analytics\RefreshWorker;

final readonly class RefreshClassProperties
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
        private int $startDelay,
        private int $interval,
        private int $expectedMaximumProcessingTime,
    ) {}

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getStartDelay(): int
    {
        return $this->startDelay;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getExpectedMaximumProcessingTime(): int
    {
        return $this->expectedMaximumProcessingTime;
    }
}
