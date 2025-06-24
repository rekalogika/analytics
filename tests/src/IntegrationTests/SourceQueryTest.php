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

use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SourceQueryTest extends KernelTestCase
{
    private function getQuery(
        ?int $queryResultLimit = null,
    ): Query {
        $summaryManager = $this->getSummaryManager();

        /** @psalm-suppress InvalidNamedArgument */
        return $summaryManager
            ->createQuery(queryResultLimit: $queryResultLimit);
    }

    private function getSummaryManager(): DefaultSummaryManager
    {
        $summaryManager = static::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(DefaultSummaryManager::class, $summaryManager);

        return $summaryManager;
    }

    public function testNoDimension(): void
    {
        $result = $this->getQuery()
            ->from(OrderSummary::class)
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('count');
        $this->assertNotNull($node);

        $sourceResult = $this->getSummaryManager()
            ->getSource($node->getTuple());

        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root ORDER BY root.id ASC',
            $dql,
        );
    }

    public function testDimension(): void
    {
        $result = $this->getQuery()
            ->from(OrderSummary::class)
            ->groupBy('customerCountry')
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('France');
        $this->assertNotNull($node);

        $sourceResult = $this->getSummaryManager()
            ->getSource($node->getTuple());

        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 WHERE IDENTITY(_a0.country) = :boundparameter0 ORDER BY root.id ASC',
            $dql,
        );
    }

    public function testDimensionProperty(): void
    {
        $result = $this->getQuery()
            ->from(OrderSummary::class)
            ->groupBy('time.civil.year')
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('2024');
        $this->assertNotNull($node);

        $sourceResult = $this->getSummaryManager()
            ->getSource($node->getTuple());

        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            "SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root WHERE REKALOGIKA_TIME_BIN(root.time, 'UTC', 'Asia/Jakarta', 'year') = :boundparameter0 ORDER BY root.id ASC",
            $dql,
        );
    }
}
