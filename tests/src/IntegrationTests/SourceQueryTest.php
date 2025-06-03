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

use Rekalogika\Analytics\Contracts\Result\Query;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Contracts\SummaryManagerRegistry;
use Rekalogika\Analytics\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SourceQueryTest extends KernelTestCase
{
    private function getQuery(
        ?int $queryResultLimit = null,
    ): Query {
        $summaryManager = $this->getSummaryManager();

        $this->assertInstanceOf(DefaultSummaryManager::class, $summaryManager);

        /** @psalm-suppress InvalidNamedArgument */
        return $summaryManager->createQuery(
            queryResultLimit: $queryResultLimit,
        );
    }

    /**
     * @return SummaryManager<object>
     */
    private function getSummaryManager(): SummaryManager
    {
        $summaryManager = static::getContainer()
            ->get(SummaryManagerRegistry::class)
            ->getManager(OrderSummary::class);

        $this->assertInstanceOf(DefaultSummaryManager::class, $summaryManager);

        return $summaryManager;
    }

    public function testNoDimension(): void
    {
        $result = $this->getQuery()
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('count');
        $this->assertNotNull($node);

        $sourceQuery = $this->getSummaryManager()
            ->createSourceQueryBuilder($node->getTuple());

        $dql = $sourceQuery->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root',
            $dql,
        );
    }

    public function testDimension(): void
    {
        $result = $this->getQuery()
            ->groupBy('customerCountry')
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('France');
        $this->assertNotNull($node);

        $sourceQuery = $this->getSummaryManager()
            ->createSourceQueryBuilder($node->getTuple());

        $dql = $sourceQuery->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 WHERE IDENTITY(_a0.country) = :boundparameter0',
            $dql,
        );
    }

    public function testDimensionProperty(): void
    {
        $result = $this->getQuery()
            ->groupBy('time.year')
            ->select('count')
            ->getResult()
            ->getTree();

        $node = $result->traverse('2024');
        $this->assertNotNull($node);

        $sourceQuery = $this->getSummaryManager()
            ->createSourceQueryBuilder($node->getTuple());

        $dql = $sourceQuery->getQuery()->getDQL();

        $this->assertEquals(
            "SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root WHERE REKALOGIKA_DATETIME_TO_SUMMARY_INTEGER(root.time, 'UTC', 'Asia/Jakarta', 'year') = :boundparameter0",
            $dql,
        );
    }
}
