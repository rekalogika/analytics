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

    /**
     * @return iterable<string,array{ChartType}>
     */
    public static function provideTwoDimensionalChartType(): iterable
    {
        yield 'grouped bar' => [ChartType::GroupedBar];
        yield 'stacked bar' => [ChartType::StackedBar];
        yield 'multi line' => [ChartType::Line];
    }

    #[DataProvider('provideTwoDimensionalChartType')]
    public function testTwoDimensionalChartYTitleIsTheMeasure(ChartType $chartType): void
    {
        $expected = $this->getYTitle(
            $this->createChart(['customerCountry'], ['price'], ChartType::Bar),
        );

        $this->assertNotNull($expected);

        $chart = $this->createChart(
            ['customerCountry', 'customerGender'],
            ['price'],
            $chartType,
        );

        $this->assertSame($expected, $this->getYTitle($chart));
    }

    private function getYTitle(Chart $chart): ?string
    {
        /** @var array{scales?:array{y?:array{title?:array{text?:string}}}} */
        $options = $chart->getOptions();

        return $options['scales']['y']['title']['text'] ?? null;
    }
}
