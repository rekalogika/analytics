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

namespace Rekalogika\Analytics\Tests\Fixtures\Refresh;

use Rekalogika\Analytics\RefreshWorker\RefreshClassProperties;
use Rekalogika\Analytics\RefreshWorker\RefreshClassPropertiesResolver;

class TestRefreshClassPropertiesResolver implements RefreshClassPropertiesResolver
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
        private int $startDelay,
        private int $interval,
        private int $expectedMaximumProcessingTime,
    ) {}

    public function getProperties(string $class): RefreshClassProperties
    {
        return new RefreshClassProperties(
            class: $this->class,
            startDelay: $this->startDelay,
            interval: $this->interval,
            expectedMaximumProcessingTime: $this->expectedMaximumProcessingTime,
        );
    }

    public function setStartDelay(int $startDelay): void
    {
        $this->startDelay = $startDelay;
    }

    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }

    public function setExpectedMaximumProcessingTime(int $expectedMaximumProcessingTime): void
    {
        $this->expectedMaximumProcessingTime = $expectedMaximumProcessingTime;
    }
}
