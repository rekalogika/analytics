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

namespace Rekalogika\Analytics\Engine\RefreshAgent;

/**
 * Locking service for the refresh agent, prevents concurrent refreshes of the
 * same summary.
 */
interface RefreshAgentLock
{
    /**
     * Acquire lock, return false if another process has acquired the lock.
     *
     * @param class-string $summaryClass
     */
    public function acquire(string $summaryClass): bool;

    /**
     * Acquire lock, wait until another process releases the lock if
     * necessary.
     *
     * @param class-string $summaryClass
     */
    public function waitAndAcquire(string $summaryClass): void;

    /**
     * Release lock. Must not throw, so that it can be called in a `finally`
     * block without replacing the exception that caused the refresh to fail.
     *
     * @param class-string $summaryClass
     */
    public function release(string $summaryClass): void;
}
