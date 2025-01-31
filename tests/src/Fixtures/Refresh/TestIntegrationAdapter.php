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

use Rekalogika\Analytics\RefreshWorker\RefreshCommand;
use Rekalogika\Analytics\RefreshWorker\RefreshFrameworkAdapter;
use Rekalogika\Analytics\RefreshWorker\RefreshScheduler;
use Symfony\Component\Clock\ClockInterface;

/**
 * @implements RefreshFrameworkAdapter<Lock>
 */
class TestIntegrationAdapter implements RefreshFrameworkAdapter
{
    /**
     * @var list<Lock>
     */
    private $locks = [];

    /**
     * @var list<string>
     */
    private $flags = [];

    private int $schedulerWorkerNumber = 0;

    /**
     * @var array<int,array{RefreshCommand<Lock>,\DateTimeInterface}>
     */
    private array $scheduledWorker = [];

    /**
     * @var RefreshScheduler<Lock>|null
     */
    private ?RefreshScheduler $scheduler = null;

    public function __construct(
        private ClockInterface $clock,
    ) {}

    /**
     * @param RefreshScheduler<Lock> $scheduler
     */
    public function setScheduler(RefreshScheduler $scheduler): void
    {
        $this->scheduler = $scheduler;
    }

    /**
     * @return RefreshScheduler<Lock>
     */
    public function getScheduler(): RefreshScheduler
    {
        if ($this->scheduler === null) {
            throw new \RuntimeException('Scheduler not set');
        }

        return $this->scheduler;
    }

    public function wakeUp(): void
    {
        $now = $this->clock->now();

        // expire locks

        $locks = $this->locks;

        foreach ($locks as $i => $lock) {
            if ($lock->getExpiration() <= $now) {
                unset($locks[$i]);
            }
        }

        $this->locks = array_values($locks);

        // execute scheduled refreshes

        foreach ($this->scheduledWorker as $i => $worker) {
            if ($worker[1] <= $now) {
                $this->getScheduler()->runWorker($worker[0]);
                unset($this->scheduledWorker[$i]);
            }
        }
    }

    public function acquireLock(string $key, float $ttl): false|Lock
    {
        foreach ($this->locks as $lock) {
            if ($lock->getKey() === $key) {
                return false;
            }
        }

        $now = $this->clock->now();

        $lock = new Lock(
            key: $key,
            ttl: $ttl,
            expiration: $now->add(new \DateInterval('PT' . $ttl . 'S')),
        );

        $this->locks[] = $lock;

        return $lock;
    }

    public function releaseLock(object $lock): void
    {
        $locks = $this->locks;

        foreach ($locks as $i => $curlock) {
            if ($lock === $curlock) {
                unset($locks[$i]);
            }
        }

        $this->locks = array_values($locks);
    }

    public function refreshLock(object $lock, float $ttl): void
    {
        foreach ($this->locks as $i => $curlock) {
            if ($lock === $curlock) {
                $this->locks[$i]->refresh($ttl);
            }
        }
    }

    public function raiseFlag(string $flag): void
    {
        $this->flags[] = $flag;
    }

    public function removeFlag(string $flag): void
    {
        $flags = $this->flags;

        foreach ($flags as $i => $f) {
            if ($f === $flag) {
                unset($flags[$i]);
            }
        }

        $this->flags = array_values($flags);
    }

    public function isFlagRaised(string $flag): bool
    {
        return \in_array($flag, $this->flags, true);
    }

    public function scheduleWorker(
        RefreshCommand $command,
        float $delay,
    ): void {
        $now = $this->clock->now();

        $this->scheduledWorker[$this->schedulerWorkerNumber] = [$command, $now->add(new \DateInterval('PT' . $delay . 'S'))];
        $this->schedulerWorkerNumber++;
    }
}
