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
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\Handler\HandlerFactory;
use Rekalogika\Analytics\Engine\Trigger\TriggerInstaller;
use Rekalogika\Analytics\Tests\App\Entity\InverseSide\Account;
use Rekalogika\Analytics\Tests\App\Entity\InverseSide\AccountProfile;
use Rekalogika\Analytics\Tests\App\Entity\InverseSide\AccountSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A summary reads a property through the inverse side of a one-to-one
 * association, `profile.tier`. DQL does not allow a path expression to the
 * inverse side, the source entities referring to a changed profile are found
 * through a join.
 */
final class InverseSideAssociationTest extends KernelTestCase
{
    private Account $account;

    private AccountProfile $profile;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = $this->getEntityManager();

        // another account, its partition must not be flagged

        $other = new Account();
        $entityManager->persist($other);
        $entityManager->persist(new AccountProfile($other, 'bronze'));

        $this->account = new Account();
        $this->profile = new AccountProfile($this->account, 'silver');
        $entityManager->persist($this->account);
        $entityManager->persist($this->profile);
        $entityManager->flush();

        $this->clearDirtyFlags();
    }

    public function testModificationOfOwningSide(): void
    {
        $this->profile->setTier('gold');
        $this->getEntityManager()->flush();

        $this->assertSame([$this->getAccountPartitionKey()], $this->getDirtyFlagKeys());
    }

    public function testDeletionOfOwningSide(): void
    {
        $entityManager = $this->getEntityManager();
        $this->account->setProfile(null);
        $entityManager->remove($this->profile);
        $entityManager->flush();

        $this->assertSame([$this->getAccountPartitionKey()], $this->getDirtyFlagKeys());
    }

    public function testNativeUpdateOfOwningSide(): void
    {
        $installer = static::getContainer()->get('rekalogika.analytics.engine.trigger.installer');
        $this->assertInstanceOf(TriggerInstaller::class, $installer);
        $installer->install();

        $this->getConnection()->executeStatement(
            'UPDATE account_profile SET tier = ? WHERE id = ?',
            ['gold', $this->profile->getId()],
        );

        $this->assertSame([$this->getAccountPartitionKey()], $this->getDirtyFlagKeys());
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function getConnection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }

    private function clearDirtyFlags(): void
    {
        $this->getConnection()->executeStatement('DELETE FROM rekalogika_summary_dirty');
    }

    private function getAccountPartitionKey(): int
    {
        $handlerFactory = static::getContainer()
            ->get('rekalogika.analytics.summary_manager.handler_factory');

        $this->assertInstanceOf(HandlerFactory::class, $handlerFactory);

        return $handlerFactory
            ->getSummary(AccountSummary::class)
            ->getPartition()
            ->createLowestPartitionFromSourceValue($this->account->getId() ?? throw new \RuntimeException('No id'))
            ->getKey();
    }

    /**
     * @return list<int>
     */
    private function getDirtyFlagKeys(): array
    {
        $keys = [];

        $result = $this->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT key FROM rekalogika_summary_dirty WHERE class = ? AND key IS NOT NULL ORDER BY key',
            [AccountSummary::class],
        );

        foreach ($result as $key) {
            $this->assertTrue(\is_int($key) || \is_string($key));
            $keys[] = (int) $key;
        }

        return $keys;
    }
}
