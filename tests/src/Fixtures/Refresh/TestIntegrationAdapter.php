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

use Rekalogika\Analytics\Engine\RefreshWorker\RefreshCommand;
use Rekalogika\Analytics\Engine\RefreshWorker\RefreshFrameworkAdapter;
use Rekalogika\Analytics\Engine\RefreshWorker\RefreshScheduler;
use Symfony\Component\Clock\ClockInterface;

/**
 * @implements RefreshFrameworkAdapter<Lock>
 */
final class TestIntegrationAdapter implements RefreshFrameworkAdapter
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
        private readonly ClockInterface $clock,
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

    #[\Override]
    public function acquireLock(string $key, int $ttl): false|Lock
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

    #[\Override]
    public function releaseLock(object $key): void
    {
        $locks = $this->locks;

        foreach ($locks as $i => $curlock) {
            if ($key === $curlock) {
                unset($locks[$i]);
            }
        }

        $this->locks = array_values($locks);
    }

    #[\Override]
    public function refreshLock(object $key, int $ttl): void
    {
        foreach ($this->locks as $i => $curlock) {
            if ($key === $curlock) {
                $this->locks[$i]->refresh($ttl);
            }
        }
    }

    #[\Override]
    public function raiseFlag(string $key, int $ttl): void
    {
        $this->flags[] = $key;
    }

    #[\Override]
    public function removeFlag(string $key): void
    {
        $flags = $this->flags;

        foreach ($flags as $i => $f) {
            if ($f === $key) {
                unset($flags[$i]);
            }
        }

        $this->flags = array_values($flags);
    }

    #[\Override]
    public function isFlagRaised(string $key): bool
    {
        return \in_array($key, $this->flags, true);
    }

    #[\Override]
    public function scheduleWorker(
        RefreshCommand $command,
        int $delay,
    ): void {
        $now = $this->clock->now();

        $this->scheduledWorker[$this->schedulerWorkerNumber] = [$command, $now->add(new \DateInterval('PT' . $delay . 'S'))];
        $this->schedulerWorkerNumber++;
    }
}
