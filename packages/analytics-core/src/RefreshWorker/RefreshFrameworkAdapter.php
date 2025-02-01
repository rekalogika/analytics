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

/**
 * @template L of object The lock object used by the framework.
 */
interface RefreshFrameworkAdapter
{
    /**
     * Acquire a lock with the given key and expiration time.
     *
     * @return false|L False if lock cannot be acquired, otherwise returns
     * an object representing the lock used by the framework.
     */
    public function acquireLock(string $key, int $ttl): false|object;

    /**
     * Release the lock.
     *
     * @param L $key The lock object returned by acquireLock.
     */
    public function releaseLock(object $key): void;

    /**
     * @param L $key
     */
    public function refreshLock(object $key, int $ttl): void;

    public function raiseFlag(string $key, int $ttl): void;

    public function removeFlag(string $key): void;

    public function isFlagRaised(string $key): bool;

    /**
     * The framework must schedule the worker to run
     * RefreshScheduler::runWorker() with the argument $command, after the
     * given delay.
     *
     * @param RefreshCommand<L> $command
     */
    public function scheduleWorker(
        RefreshCommand $command,
        int $delay,
    ): void;
}
