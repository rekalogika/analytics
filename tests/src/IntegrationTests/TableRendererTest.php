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
use Rekalogika\Analytics\Frontend\Html\TableRenderer;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TableRendererTest extends KernelTestCase
{
    /**
     * @param list<string> $dimensions
     */
    private function getResult(array $dimensions, bool $empty = false): CubeCell
    {
        $query = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions(...$dimensions);

        if ($empty) {
            $query->dice(Criteria::expr()->eq('time.civil.year', 1900));
        }

        return $query->getResult();
    }

    private function getRenderer(): TableRenderer
    {
        return static::getContainer()->get(TableRenderer::class);
    }

    public function testTable(): void
    {
        $html = $this->getRenderer()->renderTable(
            cell: $this->getResult(['customerCountry']),
            dimensions: ['customerCountry'],
            measures: ['count'],
            theme: '@RekalogikaAnalyticsFrontend/renderer.html.twig',
            throwException: true,
        );

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('analytics-measure-count', $html);
    }

    public function testTableOfEmptyResult(): void
    {
        $html = $this->getRenderer()->renderTable(
            cell: $this->getResult(['customerCountry'], empty: true),
            dimensions: ['customerCountry'],
            measures: ['count'],
            theme: '@RekalogikaAnalyticsFrontend/renderer.html.twig',
            throwException: true,
        );

        $this->assertStringNotContainsString('<table', $html);
        $this->assertStringContainsString('No result returned.', $html);
    }

    public function testTableWithBootstrapTheme(): void
    {
        $html = $this->getRenderer()->renderTable(
            cell: $this->getResult(['customerCountry']),
            dimensions: ['customerCountry'],
            measures: ['count'],
            theme: '@RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig',
            throwException: true,
        );

        $this->assertStringContainsString('<table', $html);
    }

    public function testPivotTableWithBootstrapTheme(): void
    {
        $html = $this->getRenderer()->renderPivotTable(
            cube: $this->getResult(['customerCountry', 'customerGender']),
            measures: ['count'],
            rows: ['customerCountry'],
            columns: ['customerGender', '@values'],
            theme: '@RekalogikaAnalyticsFrontend/bootstrap_5_renderer.html.twig',
            throwException: true,
        );

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('var(--bs-gray-100)', $html);
        $this->assertStringContainsString('analytics-measure-count', $html);
    }

    public function testTableWithConfiguredTheme(): void
    {
        $html = $this->getRenderer()->renderTable(
            cell: $this->getResult(['customerCountry']),
            dimensions: ['customerCountry'],
            measures: ['count'],
            throwException: true,
        );

        $this->assertStringContainsString('<table', $html);
    }
}
