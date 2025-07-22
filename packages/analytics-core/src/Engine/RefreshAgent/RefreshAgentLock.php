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

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Exception\RuntimeException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Store\DoctrineDbalPostgreSqlStore;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Locking service for the refresh agent.
 */
final class RefreshAgentLock implements ResetInterface
{
    /**
     * @var \WeakMap<Connection,DoctrineDbalPostgreSqlStore>
     */
    private \WeakMap $connectionToStore;

    /**
     * @var array<class-string,Key>
     */
    private array $summaryClassToKey = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->connectionToStore = new \WeakMap();
    }

    #[\Override]
    public function reset(): void
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->connectionToStore = new \WeakMap();
        $this->summaryClassToKey = [];
    }

    /**
     * @param class-string $summaryClass
     */
    private function getConnectionBySummaryClass(string $summaryClass): Connection
    {
        $manager = $this->managerRegistry
            ->getManagerForClass($summaryClass);

        if ($manager === null) {
            throw new RuntimeException(\sprintf(
                'No manager found for class "%s".',
                $summaryClass,
            ));
        }

        if (!$manager instanceof EntityManagerInterface) {
            throw new RuntimeException(\sprintf(
                'The manager for class "%s" is not an instance of EntityManagerInterface.',
                $summaryClass,
            ));
        }

        return $manager->getConnection();
    }

    /** @psalm-suppress InvalidNullableReturnType */
    private function getStoreByConnection(
        Connection $connection,
    ): DoctrineDbalPostgreSqlStore {
        /** @psalm-suppress PossiblyNullArgument */
        /** @psalm-suppress NullableReturnStatement */
        return $this->connectionToStore[$connection] ??= new DoctrineDbalPostgreSqlStore($connection);
    }

    /**
     * @param class-string $summaryClass
     */
    private function getStoreBySummaryClass(
        string $summaryClass,
    ): DoctrineDbalPostgreSqlStore {
        $connection = $this->getConnectionBySummaryClass($summaryClass);

        return $this->getStoreByConnection($connection);
    }

    /**
     * @param class-string $summaryClass
     */
    private function getKey(string $summaryClass): Key
    {
        return $this->summaryClassToKey[$summaryClass]
            ?? new Key(\sprintf('rekalogika_analytics_%s', $summaryClass));
    }

    private function removeKey(string $summaryClass): void
    {
        unset($this->summaryClassToKey[$summaryClass]);
    }

    /**
     * Acquire lock, return false if another process has acquired the lock.
     *
     * @param class-string $summaryClass
     */
    public function acquire(string $summaryClass): bool
    {
        $store = $this->getStoreBySummaryClass($summaryClass);
        $key = $this->getKey($summaryClass);

        try {
            $store->save($key);

            return true;
        } catch (LockConflictedException) {
            return false;
        }
    }

    /**
     * Release key
     *
     * @param class-string $summaryClass
     */
    public function release(string $summaryClass): void
    {
        $store = $this->getStoreBySummaryClass($summaryClass);
        $key = $this->getKey($summaryClass);
        $store->delete($key);
        $this->removeKey($summaryClass);
    }
}
