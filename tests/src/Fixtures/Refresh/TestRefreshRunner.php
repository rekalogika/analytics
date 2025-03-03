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

use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\RefreshWorker\RefreshRunner;
use Symfony\Component\Clock\ClockInterface;

final class TestRefreshRunner implements RefreshRunner
{
    private int $runNumber = 0;

    /**
     * @var array<int,string>
     */
    private array $runs = [];

    /**
     * @var array<int,string>
     */
    private array $runFinishes = [];


    public function __construct(
        private readonly ClockInterface $clock,
        private int $processingTime,
    ) {}

    public function setProcessingTime(int $processingTime): void
    {
        $this->processingTime = $processingTime;
    }

    #[\Override]
    public function refresh(string $class, ?Partition $partition): void
    {
        $this->runs[$this->runNumber] = $this->clock->now()->format('Y-m-d H:i:s');
        $this->clock->sleep($this->processingTime);
        $this->runFinishes[$this->runNumber] = $this->clock->now()->format('Y-m-d H:i:s');
        $this->runNumber++;
    }

    /**
     * @return array<int,string>
     */
    public function getRunStarts(): array
    {
        return $this->runs;
    }

    /**
     * @return array<int,string>
     */
    public function getRunFinishes(): array
    {
        return $this->runFinishes;
    }
}
