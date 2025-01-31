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
    public function acquireLock(string $key, float $ttl): false|object;

    /**
     * Release the lock.
     *
     * @param L $lock The lock object returned by acquireLock.
     */
    public function releaseLock(object $lock): void;

    /**
     * @param L $lock
     */
    public function refreshLock(object $lock, float $ttl): void;

    public function raiseFlag(string $flag): void;

    public function removeFlag(string $flag): void;

    public function isFlagRaised(string $flag): bool;

    /**
     * @param RefreshCommand<L> $command
     */
    public function scheduleWorker(
        RefreshCommand $command,
        float $delay,
    ): void;
}
