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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
use Rekalogika\Analytics\Engine\SummaryQuery\DefaultSourceResult;
use Rekalogika\Analytics\Tests\App\Entity\Country;
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

        $this->assertInstanceOf(DefaultSourceResult::class, $sourceResult);
        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root WHERE root.id > :minId ORDER BY root.id ASC',
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

        $this->assertInstanceOf(DefaultSourceResult::class, $sourceResult);
        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 WHERE root.id > :minId AND IDENTITY(_a0.country) = :boundparameter0 ORDER BY root.id ASC',
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

        $this->assertInstanceOf(DefaultSourceResult::class, $sourceResult);
        $dql = $sourceResult->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            "SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root WHERE root.id > :minId AND REKALOGIKA_TIME_BIN(root.time, 'UTC', 'Asia/Jakarta', 'YYYY') = :boundparameter0 ORDER BY root.id ASC",
            $dql,
        );
    }

    public function testCount(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertInstanceOf(EntityManager::class, $entityManager);

        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        $oneCountry = $entityManager
            ->getRepository(Country::class)
            ->findOneBy([]);

        // query

        $query = $summaryManager
            ->createQuery()
            ->from(OrderSummary::class)
            ->select('count', 'price')
            ->groupBy('customerCountry')
            ->addGroupBy('itemCategory')
            ->addGroupBy('customerGender')
            ->where(Criteria::expr()->neq('customerCountry', $oneCountry));

        $result = $query->getResult();

        // test all rows

        foreach ($result->getTable() as $currentRow) {
            $this->testOneCount($currentRow);
        }
    }

    private function testOneCount(Row $row): void
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        /** @psalm-suppress MixedAssignment */
        $precounted = $row->getMeasures()->get('count')?->getValue() ?? 0;
        $this->assertIsInt($precounted);

        $sourceResult = $summaryManager->getSource($row->getTuple());

        $count = 0;
        $pages = $sourceResult->withItemsPerPage(1000)->getPages();

        foreach ($pages as $page) {
            foreach ($page as $item) {
                $count++;
            }
        }

        if ($count !== $precounted) {
            $this->fail(
                \sprintf(
                    'Count from source result (%d) does not match the precounted value (%d).',
                    $count,
                    $precounted,
                ),
            );
        }

        $this->assertEquals($precounted, $count, 'Count from source result should match the precounted value.');
    }
}
