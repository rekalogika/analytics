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

use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Chart\ChartType;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Chartjs\Model\Chart;

final class ChartGeneratorTest extends KernelTestCase
{
    /**
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     */
    private function createChart(
        array $dimensions,
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions(...$dimensions)
            ->getResult();

        return static::getContainer()
            ->get(ChartGenerator::class)
            ->createChart(
                cube: $result,
                dimensions: $dimensions,
                measures: $measures,
                chartType: $chartType,
            );
    }

    /**
     * @return iterable<string,array{string,string}>
     */
    public static function provideAutoChartType(): iterable
    {
        yield 'sequential dimension' => ['time.civil.month.month', Chart::TYPE_LINE];
        yield 'non-sequential dimension' => ['customerCountry', Chart::TYPE_BAR];
    }

    #[DataProvider('provideAutoChartType')]
    public function testAutoChartType(string $dimension, string $expectedType): void
    {
        $chart = $this->createChart([$dimension], ['count']);

        $this->assertSame($expectedType, $chart->getType());
    }
}
