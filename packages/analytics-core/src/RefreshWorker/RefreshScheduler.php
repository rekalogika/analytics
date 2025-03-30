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

use Rekalogika\Analytics\Contracts\Model\Partition;

/**
 * @template L of object The lock object used by the framework.
 */
final readonly class RefreshScheduler
{
    /**
     * @todo change the parameters to be configurable
     * @param ?RefreshFrameworkAdapter<L> $adapter
     */
    public function __construct(
        private ?RefreshFrameworkAdapter $adapter,
        private RefreshRunner $runner,
        private RefreshClassPropertiesResolver $propertiesResolver,
    ) {}

    private function createLockKey(
        string $class,
        ?Partition $partition,
    ): string {
        return \sprintf(
            '%s:%s:%s',
            $class,
            $partition?->getLevel() ?? '-',
            $partition?->getKey() ?? '-',
        );
    }

    /**
     * @param class-string $class
     */
    public function scheduleWorker(
        string $class,
        ?Partition $partition,
    ): void {
        if ($this->adapter === null) {
            return;
        }

        $identifier = $this->createLockKey($class, $partition);
        $properties = $this->propertiesResolver->getProperties($class);

        // acquire lock
        $lock = $this->adapter->acquireLock(
            key: $identifier,
            ttl: $properties->getStartDelay()
                + $properties->getInterval()
                + $properties->getExpectedMaximumProcessingTime(),
        );

        if (\is_object($lock)) {
            // if lock acquired, schedule primary worker

            $command = new RefreshCommand(
                primary: true,
                class: $class,
                partition: $partition,
                key: $lock,
            );

            $this->adapter->scheduleWorker($command, $properties->getStartDelay());
        } else {
            // if lock not acquired, raise flag
            $this->adapter->raiseFlag(
                key: $identifier,
                ttl: $properties->getStartDelay()
                    + $properties->getInterval()
                    + $properties->getExpectedMaximumProcessingTime(),
            );
        }
    }

    /**
     * @param RefreshCommand<L> $command
     */
    public function runWorker(RefreshCommand $command): void
    {
        if ($this->adapter === null) {
            return;
        }

        $isPrimary = $command->isPrimary();
        $class = $command->getClass();
        $partition = $command->getPartition();
        $key = $command->getKey();
        $properties = $this->propertiesResolver->getProperties($class);

        $identifier = $this->createLockKey($class, $partition);

        // in the secondary run, if the flag is not raised, release lock, and
        // return. else continue like the primary run.

        if (
            !$isPrimary
            && !$this->adapter->isFlagRaised($identifier)
        ) {
            $this->adapter->releaseLock($key);

            return;
        }

        // refresh lock

        $this->adapter->refreshLock(
            key: $key,
            ttl: $properties->getExpectedMaximumProcessingTime()
                + 2 * $properties->getInterval(),
        );

        // remove flag

        $this->adapter->removeFlag($identifier);

        // the worker does the actual work here

        $this->refresh($class, $partition);

        // refresh lock again

        $this->adapter->refreshLock(
            key: $key,
            ttl: 2 * $properties->getInterval(),
        );

        // schedule secondary worker

        $command = new RefreshCommand(
            primary: false,
            class: $class,
            partition: $partition,
            key: $key,
        );

        $this->adapter->scheduleWorker($command, $properties->getInterval());
    }

    /**
     * @param class-string $class
     */
    private function refresh(
        string $class,
        ?Partition $partition,
    ): void {
        $this->runner->refresh($class, $partition);
    }
}
