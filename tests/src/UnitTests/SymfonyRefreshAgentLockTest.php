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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Rekalogika\Analytics\Engine\RefreshAgent\SymfonyRefreshAgentLock;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

final class SymfonyRefreshAgentLockTest extends TestCase
{
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

    public function testLockIsExclusive(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());

        $first = new SymfonyRefreshAgentLock($lockFactory);
        $second = new SymfonyRefreshAgentLock($lockFactory);

        $this->assertTrue($first->acquire(OrderSummary::class));
        $this->assertFalse($second->acquire(OrderSummary::class));

        $first->release(OrderSummary::class);

        $second->waitAndAcquire(OrderSummary::class);
        $this->assertFalse($first->acquire(OrderSummary::class));

        $second->release(OrderSummary::class);

        $this->assertTrue($first->acquire(OrderSummary::class));
        $first->release(OrderSummary::class);
    }

    public function testRefreshExtendsHeldLock(): void
    {
        $store = $this->createMock(PersistingStoreInterface::class);
        $store->expects($this->once())->method('save');

        // once on acquire, once on refresh

        $store->expects($this->exactly(2))
            ->method('putOffExpiration')
            ->with($this->anything(), 60.0);

        $lock = new SymfonyRefreshAgentLock(new LockFactory($store), ttl: 60.0);

        $lock->refresh(OrderSummary::class);
        $this->assertTrue($lock->acquire(OrderSummary::class));
        $lock->refresh(OrderSummary::class);
    }

    public function testRefreshWithoutTtl(): void
    {
        $store = $this->createMock(PersistingStoreInterface::class);
        $store->expects($this->never())->method('putOffExpiration');

        $lock = new SymfonyRefreshAgentLock(new LockFactory($store), ttl: null);

        $this->assertTrue($lock->acquire(OrderSummary::class));
        $lock->refresh(OrderSummary::class);
    }

    public function testFailedReleaseIsLogged(): void
    {
        $store = $this->createStub(PersistingStoreInterface::class);
        $store->method('delete')->willThrowException(new \RuntimeException());

        /** @var \ArrayObject<int,string> */
        $levels = new \ArrayObject();

        $lock = new SymfonyRefreshAgentLock(
            lockFactory: new LockFactory($store),
            logger: $this->createLogger($levels),
        );

        $this->assertTrue($lock->acquire(OrderSummary::class));
        $lock->release(OrderSummary::class);

        $this->assertSame(['error'], $levels->getArrayCopy());

        // releasing a lock that is not held does nothing

        $lock->release(OrderSummary::class);
        $this->assertSame(['error'], $levels->getArrayCopy());
    }
}
