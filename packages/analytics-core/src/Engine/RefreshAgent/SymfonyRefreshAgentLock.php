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

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Exception\ExceptionInterface as LockException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * Lock using a symfony/lock `LockFactory`, so that any of its stores can be
 * used.
 *
 * With an expiring store, the lock expires after `$ttl` seconds unless
 * `refresh()` is called. Avoid a store using the same database connection as
 * the summary: if the refresh fails in an aborted transaction, the lock cannot
 * be released.
 */
final class SymfonyRefreshAgentLock implements RefreshAgentLock
{
    /**
     * @var array<class-string,LockInterface>
     */
    private array $locks = [];

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly ?float $ttl = 300.0,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param class-string $summaryClass
     */
    private function getLock(string $summaryClass): LockInterface
    {
        return $this->locks[$summaryClass] ??= $this->lockFactory->createLock(
            resource: \sprintf('rekalogika_analytics_%s', $summaryClass),
            ttl: $this->ttl,
        );
    }

    #[\Override]
    public function acquire(string $summaryClass): bool
    {
        $lock = $this->getLock($summaryClass);

        if ($lock->acquire()) {
            return true;
        }

        unset($this->locks[$summaryClass]);

        return false;
    }

    #[\Override]
    public function waitAndAcquire(string $summaryClass): void
    {
        $this->getLock($summaryClass)->acquire(true);
    }

    /**
     * Extends the lifetime of the lock by `$ttl` seconds, if held. Throws if
     * the lock has expired and has been acquired by another process.
     *
     * @param class-string $summaryClass
     */
    public function refresh(string $summaryClass): void
    {
        if ($this->ttl === null) {
            return;
        }

        ($this->locks[$summaryClass] ?? null)?->refresh();
    }

    #[\Override]
    public function release(string $summaryClass): void
    {
        $lock = $this->locks[$summaryClass] ?? null;
        unset($this->locks[$summaryClass]);

        if ($lock === null) {
            return;
        }

        try {
            $lock->release();
        } catch (LockException $e) {
            $this->logger?->error(
                'Failed to release the refresh lock for "{summaryClass}", the lock might be held until it expires.',
                [
                    'summaryClass' => $summaryClass,
                    'exception' => $e,
                ],
            );
        }
    }
}
