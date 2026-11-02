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

use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Engine\Entity\DirtyFlag;
use Rekalogika\Analytics\Engine\RefreshAgent\DefaultRefreshAgentStrategy;
use Rekalogika\Analytics\Engine\SummaryRefresher\SummaryRefresherFactory;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Rekalogika\Analytics\Tests\App\Entity\TestIntegerPartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StaleDirtyFlagTest extends KernelTestCase
{
    /**
     * Dirty flags of a level no longer used by the partition, e.g. after a
     * change of the partition class, are never processed. The refresh must
     * remove them, otherwise they accumulate.
     */
    public function testRefreshRemovesFlagsOfUnusedLevels(): void
    {
        $entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->assertNotContains(3, TestIntegerPartition::getAllLevels());

        $entityManager->persist(new DirtyFlag(OrderSummary::class, 3, 0));
        $entityManager->flush();
        $entityManager->clear();

        $factory = static::getContainer()
            ->get('rekalogika.analytics.summary_refresher_factory');

        $this->assertInstanceOf(SummaryRefresherFactory::class, $factory);

        $refresher = $factory->createSummaryRefresher(OrderSummary::class);
        $refresher->refresh();

        $staleFlags = $entityManager
            ->getRepository(DirtyFlag::class)
            ->findBy(['class' => OrderSummary::class, 'level' => 3]);

        $this->assertSame([], $staleFlags);
        $this->assertNull(
            $refresher->getNextRefreshTime(new DefaultRefreshAgentStrategy()),
        );
    }
}
