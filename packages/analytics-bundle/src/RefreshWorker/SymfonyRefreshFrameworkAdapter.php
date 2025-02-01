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

namespace Rekalogika\Analytics\Bundle\RefreshWorker;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Rekalogika\Analytics\RefreshWorker\RefreshCommand;
use Rekalogika\Analytics\RefreshWorker\RefreshFrameworkAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * @implements RefreshFrameworkAdapter<Key>
 */
final readonly class SymfonyRefreshFrameworkAdapter implements RefreshFrameworkAdapter
{
    private CacheInterface $cache;

    public function __construct(
        private LockFactory $lockFactory,
        CacheItemPoolInterface $cache,
        private MessageBusInterface $messageBus,
    ) {
        $this->cache = new Psr16Cache($cache);
    }

    private function normalizeKey(string $key): string
    {
        $result = preg_replace('/[^a-zA-Z0-9]/', '', self::class . $key);

        if (!\is_string($result)) {
            throw new \RuntimeException('Invalid key name');
        }

        return $result;
    }

    public function acquireLock(string $key, int $ttl): false|object
    {
        $key = new Key($this->normalizeKey($key));

        $lock = $this->lockFactory
            ->createLockFromKey(
                key: $key,
                ttl: $ttl,
                autoRelease: false,
            );

        $result = $lock->acquire(blocking: false);

        if ($result === false) {
            return false;
        }

        return $key;
    }

    public function releaseLock(object $key): void
    {
        $lock = $this->lockFactory->createLockFromKey($key);
        $lock->release();
    }

    public function refreshLock(object $key, int $ttl): void
    {
        $lock = $this->lockFactory->createLockFromKey($key);
        $lock->refresh($ttl);
    }

    public function raiseFlag(string $key, int $ttl): void
    {
        $this->cache->set($this->normalizeKey($key), true, $ttl);
    }

    public function removeFlag(string $key): void
    {
        $this->cache->delete($this->normalizeKey($key));
    }

    public function isFlagRaised(string $key): bool
    {
        return $this->cache->has($this->normalizeKey($key));
    }

    public function scheduleWorker(RefreshCommand $command, int $delay): void
    {
        $envelope = new Envelope($command, [
            new DelayStamp($delay * 1000),
        ]);

        $this->messageBus->dispatch($envelope);
    }
}
