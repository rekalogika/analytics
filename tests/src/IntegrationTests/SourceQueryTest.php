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
use Rekalogika\Analytics\Engine\SourceEntities\DefaultSourceEntities;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryManager\DefaultSummaryManager;
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

    private function getSourceEntitiesFactory(): SourceEntitiesFactory
    {
        $sourceEntitiesFactory = static::getContainer()
            ->get('rekalogika.analytics.source_entities_factory');

        $this->assertInstanceOf(
            SourceEntitiesFactory::class,
            $sourceEntitiesFactory,
        );

        return $sourceEntitiesFactory;
    }

    public function testNoDimension(): void
    {
        $coordinates = $this->getQuery()
            ->from(OrderSummary::class)
            ->getResult()
            ->getCube()
            ->getCoordinates();

        $sourceEntities = $this
            ->getSourceEntitiesFactory()
            ->getSourceEntities($coordinates);

        $this->assertInstanceOf(DefaultSourceEntities::class, $sourceEntities);
        $dql = $sourceEntities->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root WHERE root.id > :minId ORDER BY root.id ASC',
            $dql,
        );
    }

    public function testDimension(): void
    {
        $result = $this->getQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry')
            ->getResult();

        $apexCube = $result->getCube();
        $slices = $apexCube->drillDown('customerCountry');
        $coordinates = $slices->first()?->getCoordinates();

        $this->assertNotNull($coordinates);

        $sourceEntities = $this
            ->getSourceEntitiesFactory()
            ->getSourceEntities($coordinates);

        $this->assertInstanceOf(DefaultSourceEntities::class, $sourceEntities);
        $dql = $sourceEntities->getQueryBuilder()->getQuery()->getDQL();

        $this->assertEquals(
            'SELECT root FROM Rekalogika\Analytics\Tests\App\Entity\Order root LEFT JOIN root.customer _a0 WHERE root.id > :minId AND IDENTITY(_a0.country) = :boundparameter0 ORDER BY root.id ASC',
            $dql,
        );
    }

    public function testDimensionProperty(): void
    {
        $result = $this->getQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.year')
            ->getResult();

        $apexCube = $result->getCube();
        $slices = $apexCube->drillDown('time.civil.year');
        $coordinates = $slices->first()?->getCoordinates();

        $this->assertNotNull($coordinates);

        $sourceEntities = $this
            ->getSourceEntitiesFactory()
            ->getSourceEntities($coordinates);

        $this->assertInstanceOf(DefaultSourceEntities::class, $sourceEntities);
        $dql = $sourceEntities->getQueryBuilder()->getQuery()->getDQL();

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
            ->withDimensions('customerCountry')
            ->addDimension('itemCategory')
            ->addDimension('customerGender')
            ->dice(Criteria::expr()->neq('customerCountry', $oneCountry));

        $result = $query->getResult();

        // test all rows

        foreach ($result->getTable() as $currentRow) {
            $this->testOneCount($currentRow);
        }
    }

    private function testOneCount(Row $row): void
    {
        /** @psalm-suppress MixedAssignment */
        $precounted = $row->getMeasures()->get('count')?->getValue() ?? 0;
        $this->assertIsInt($precounted);

        $sourceEntities = $this
            ->getSourceEntitiesFactory()
            ->getSourceEntities($row->getCoordinates());

        $count = 0;
        $pages = $sourceEntities->withItemsPerPage(1000)->getPages();

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
