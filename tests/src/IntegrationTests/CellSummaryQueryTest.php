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
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Engine\SummaryQuery\Output\DefaultCell;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The summary query of a cell must compute the same measures as the cell.
 */
final class CellSummaryQueryTest extends KernelTestCase
{
    public function testSummaryQueryOfCells(): void
    {
        $cells = $this->getSummaryManager()
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerCountry', 'time.civil.year')
            ->getResult()
            ->drillDown(['customerCountry', 'time.civil.year']);

        $this->assertGreaterThan(0, \count($cells));

        foreach ($cells as $cell) {
            $this->assertSummaryQueryMatches($cell);
        }
    }

    public function testSummaryQueryOfApexCell(): void
    {
        $cell = $this->getSummaryManager()
            ->createQuery()
            ->from(OrderSummary::class)
            ->getResult();

        $this->assertSummaryQueryMatches($cell);
    }

    public function testSummaryQueryOfDicedCells(): void
    {
        $cells = $this->getSummaryManager()
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('customerType')
            ->dice(Criteria::expr()->in('time.civil.month.month', [202301, 202304]))
            ->getResult()
            ->drillDown('customerType');

        $this->assertGreaterThan(0, \count($cells));

        foreach ($cells as $cell) {
            $this->assertSummaryQueryMatches($cell);
        }
    }

    private function assertSummaryQueryMatches(CubeCell $cell): void
    {
        $this->assertInstanceOf(DefaultCell::class, $cell);

        $queryComponents = $cell->getSummaryQueryComponents();
        $this->assertNotNull($queryComponents);

        // the measures are selected with an alias derived from their name
        $countAlias = 'a_' . hash('xxh128', 'count');
        $priceAlias = 'a_' . hash('xxh128', 'price');

        /** @var list<array<string,mixed>> */
        $result = $queryComponents->getQuery()->getArrayResult();
        $this->assertCount(1, $result);

        $this->assertEquals(
            $cell->getMeasures()->get('count')?->getRawValue(),
            $result[0][$countAlias],
        );

        $this->assertEquals(
            $cell->getMeasures()->get('price')?->getRawValue(),
            $result[0][$priceAlias],
        );
    }

    private function getSummaryManager(): SummaryManager
    {
        $summaryManager = self::getContainer()->get(SummaryManager::class);
        $this->assertInstanceOf(SummaryManager::class, $summaryManager);

        return $summaryManager;
    }
}
