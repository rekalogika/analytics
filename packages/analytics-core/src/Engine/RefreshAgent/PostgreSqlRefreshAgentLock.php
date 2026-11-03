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
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Contracts\Exception\RuntimeException;

/**
 * The default lock, using PostgreSQL session-level advisory locks on the
 * connection of the summary. The lock is released when the database session
 * ends, e.g. when the process dies.
 */
final readonly class PostgreSqlRefreshAgentLock implements RefreshAgentLock
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ?LoggerInterface $logger = null,
    ) {}

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

    /**
     * Same key as the one used by `DoctrineDbalPostgreSqlStore` from
     * symfony/lock, which was used previously.
     *
     * @param class-string $summaryClass
     */
    private function getLockKey(string $summaryClass): int
    {
        return crc32(\sprintf('rekalogika_analytics_%s', $summaryClass));
    }

    #[\Override]
    public function acquire(string $summaryClass): bool
    {
        $connection = $this->getConnectionBySummaryClass($summaryClass);

        return $connection->fetchOne(
            'SELECT pg_try_advisory_lock(:key)',
            ['key' => $this->getLockKey($summaryClass)],
            ['key' => ParameterType::INTEGER],
        ) === true;
    }

    #[\Override]
    public function waitAndAcquire(string $summaryClass): void
    {
        $connection = $this->getConnectionBySummaryClass($summaryClass);

        $connection->executeStatement(
            'SELECT pg_advisory_lock(:key)',
            ['key' => $this->getLockKey($summaryClass)],
            ['key' => ParameterType::INTEGER],
        );
    }

    /**
     * If the connection is unusable, e.g. it is in an aborted transaction,
     * closes the connection to release the lock.
     */
    #[\Override]
    public function release(string $summaryClass): void
    {
        $connection = $this->getConnectionBySummaryClass($summaryClass);
        $key = $this->getLockKey($summaryClass);

        try {
            $this->unlock($connection, $key);

            return;
        } catch (DBALException $e) {
            // the connection is unusable, e.g. it is in an aborted transaction.
            // closing it ends the database session, which releases the
            // advisory lock. dbal reconnects on the next use.

            $connection->close();
        }

        try {
            // a persistent connection keeps the database session, and the
            // lock, after closing. the reconnected session rolled back the
            // transaction, so the lock can be released now. with a
            // non-persistent connection, this is a no-op.

            $this->unlock($connection, $key);
        } catch (DBALException $e2) {
            $this->logger?->error(
                'Failed to release the refresh lock for "{summaryClass}", the lock might be held until the database session ends.',
                [
                    'summaryClass' => $summaryClass,
                    'exception' => $e2,
                ],
            );

            return;
        }

        $this->logger?->warning(
            'Failed to release the refresh lock for "{summaryClass}", closed the database connection to release it.',
            [
                'summaryClass' => $summaryClass,
                'exception' => $e,
            ],
        );
    }

    /**
     * Releases all the holds of the lock by the current session, a session
     * can hold the same lock multiple times.
     */
    private function unlock(Connection $connection, int $key): void
    {
        do {
            $result = $connection->executeQuery(
                "SELECT pg_advisory_unlock(objid::bigint) FROM pg_locks WHERE locktype = 'advisory' AND mode = 'ExclusiveLock' AND classid = 0 AND objid = :key AND objsubid = 1 AND pid = pg_backend_pid()",
                ['key' => $key],
                ['key' => ParameterType::INTEGER],
            );
        } while ($result->rowCount() !== 0);
    }
}
