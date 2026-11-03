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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Engine\RefreshAgent\PostgreSqlRefreshAgentLock;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PostgreSqlRefreshAgentLockTest extends KernelTestCase
{
    /**
     * A connection outside the static connection of the test bundle, which
     * keeps the database session open on close.
     */
    private function createConnection(bool $persistent): Connection
    {
        $url = $_SERVER['DATABASE_URL'] ?? null;
        $this->assertIsString($url);

        $params = (new DsnParser(['postgresql' => 'pdo_pgsql']))->parse($url);
        $params['driverOptions'] = [\PDO::ATTR_PERSISTENT => $persistent];

        return DriverManager::getConnection($params);
    }

    private function createLock(
        Connection $connection,
        LoggerInterface $logger,
    ): PostgreSqlRefreshAgentLock {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);

        return new PostgreSqlRefreshAgentLock(
            managerRegistry: $managerRegistry,
            logger: $logger,
        );
    }

    /**
     * @param \ArrayObject<int,string> $levels receives the levels of the
     * logged messages
     */
    private function createLogger(\ArrayObject $levels): LoggerInterface
    {
        return new class ($levels) extends AbstractLogger {
            /**
             * @param \ArrayObject<int,string> $levels
             */
            public function __construct(private readonly \ArrayObject $levels) {}

            #[\Override]
            public function log($level, \Stringable|string $message, array $context = []): void
            {
                \assert(\is_string($level));
                $this->levels->append($level);
            }
        };
    }

    private function countAdvisoryLocks(Connection $connection): int
    {
        $count = $connection->fetchOne(
            "SELECT count(*) FROM pg_locks WHERE locktype = 'advisory' AND pid = pg_backend_pid()",
        );

        $this->assertIsInt($count);

        return $count;
    }

    /**
     * @return iterable<string,array{bool}>
     */
    public static function persistentProvider(): iterable
    {
        yield 'non-persistent' => [false];
        yield 'persistent' => [true];
    }

    /**
     * Releasing the lock on an unusable connection falls back to closing the
     * connection, and logs it. Afterwards, the lock is free, including with a
     * persistent connection that keeps the database session, and the lock,
     * after closing.
     */
    #[DataProvider('persistentProvider')]
    public function testReleaseOnUnusableConnection(bool $persistent): void
    {
        $connection = $this->createConnection($persistent);
        /** @var \ArrayObject<int,string> */
        $levels = new \ArrayObject();
        $logger = $this->createLogger($levels);
        $lock = $this->createLock($connection, $logger);

        // abort a transaction that the lock does not know about

        $connection->beginTransaction();
        $this->assertTrue($lock->acquire(OrderSummary::class));

        try {
            $connection->executeQuery('SELECT 1/0');
            $this->fail('The query should have failed.');
        } catch (DriverException $e) {
            $this->assertSame('22012', $e->getSQLState());
        }

        $lock->release(OrderSummary::class);

        $this->assertSame(['warning'], $levels->getArrayCopy());
        $this->assertFalse($connection->isTransactionActive());
        $this->assertSame(0, $this->countAdvisoryLocks($connection));

        // the lock is free for another session

        $other = $this->createConnection(false);
        $otherLock = $this->createLock($other, $logger);
        $this->assertTrue($otherLock->acquire(OrderSummary::class));
        $otherLock->release(OrderSummary::class);
        $other->close();

        // and can be acquired again on the same connection

        $this->assertTrue($lock->acquire(OrderSummary::class));
        $this->assertSame(1, $this->countAdvisoryLocks($connection));
        $lock->release(OrderSummary::class);
        $this->assertSame(0, $this->countAdvisoryLocks($connection));

        $this->assertSame(['warning'], $levels->getArrayCopy());

        $connection->close();
    }

    public function testLockIsExclusive(): void
    {
        /** @var \ArrayObject<int,string> */
        $levels = new \ArrayObject();
        $logger = $this->createLogger($levels);

        $first = $this->createConnection(false);
        $firstLock = $this->createLock($first, $logger);

        $second = $this->createConnection(false);
        $secondLock = $this->createLock($second, $logger);

        $this->assertTrue($firstLock->acquire(OrderSummary::class));
        $this->assertFalse($secondLock->acquire(OrderSummary::class));

        $firstLock->release(OrderSummary::class);

        $this->assertTrue($secondLock->acquire(OrderSummary::class));
        $this->assertFalse($firstLock->acquire(OrderSummary::class));

        $secondLock->release(OrderSummary::class);

        $this->assertSame(0, $this->countAdvisoryLocks($first));
        $this->assertSame(0, $this->countAdvisoryLocks($second));
        $this->assertSame([], $levels->getArrayCopy());

        $first->close();
        $second->close();
    }
}
