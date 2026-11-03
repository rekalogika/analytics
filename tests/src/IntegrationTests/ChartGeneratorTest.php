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
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Chart\ChartType;
use Rekalogika\Analytics\Frontend\Chart\UnsupportedData;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Chartjs\Model\Chart;

final class ChartGeneratorTest extends KernelTestCase
{
    /**
     * @param list<string> $dimensions
     * @param list<string> $measures
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

    #[DataProvider('provideTwoDimensionalChartType')]
    public function testTwoDimensionalChartLegendPosition(ChartType $chartType): void
    {
        $chart = $this->createChart(
            ['customerCountry', 'customerGender'],
            ['price'],
            $chartType,
        );

        /** @var array{plugins?:array{legend?:array<string,mixed>}} */
        $options = $chart->getOptions();
        $legend = $options['plugins']['legend'] ?? [];

        $this->assertSame('top', $legend['position'] ?? null);
        $this->assertArrayNotHasKey('labels', $legend);
    }

    #[DataProvider('provideTwoDimensionalChartType')]
    public function testMissingValueIsNullInTwoDimensionalChart(ChartType $chartType): void
    {
        $dimensions = ['time.civil.month.month', 'customerGender'];

        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions(...$dimensions)
            ->getResult();

        $chart = static::getContainer()
            ->get(ChartGenerator::class)
            ->createChart(
                cube: $result,
                dimensions: $dimensions,
                measures: ['price'],
                chartType: $chartType,
            );

        $expectedNulls = [];

        foreach ($result->drillDown($dimensions[0]) as $firstCell) {
            $i = 0;

            foreach ($firstCell->drillDown($dimensions[1]) as $secondCell) {
                $expectedNulls[$i][] = $secondCell->getMeasures()->get('price')?->getValue() === null;
                $i++;
            }
        }

        $this->assertContains(true, array_merge(...$expectedNulls), 'Fixture has no missing value');

        /** @var array{datasets:list<array{data:list<int|float|null>}>} */
        $data = $chart->getData();

        $actualNulls = array_map(
            static fn(array $dataSet): array => array_map(
                static fn(int|float|null $value): bool => $value === null,
                $dataSet['data'],
            ),
            $data['datasets'],
        );

        $this->assertSame($expectedNulls, $actualNulls);
    }

    public function testMeasuresWithDifferentUnitsGetTheirOwnAxis(): void
    {
        $chart = $this->createChart(
            ['customerCountry'],
            ['count', 'price', 'uniqueCustomers', 'maxPrice'],
            ChartType::Bar,
        );

        /** @var array{datasets:list<array{yAxisID?:string}>} */
        $data = $chart->getData();

        $this->assertSame(
            ['y', 'y1', 'y', 'y1'],
            array_map(
                static fn(array $dataSet): ?string => $dataSet['yAxisID'] ?? null,
                $data['datasets'],
            ),
        );

        /** @var array{scales:array<string,array{position?:string,title:array{display:bool,text?:string}}>} */
        $options = $chart->getOptions();
        $scales = $options['scales'];

        $this->assertSame(['x', 'y', 'y1'], array_keys($scales));

        // unitless measures, the legend tells them apart
        $this->assertFalse($scales['y']['title']['display']);

        $this->assertSame('Monetary Value (EUR)', $scales['y1']['title']['text'] ?? null);
        $this->assertSame('right', $scales['y1']['position'] ?? null);
    }

    public function testMissingValueIsNullInPieChart(): void
    {
        $result = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions('time.civil.month.month', 'customerGender')
            ->getResult();

        // Find a month with a missing value for one of the genders.

        $cube = null;
        $expectedNulls = [];

        foreach ($result->drillDown('time.civil.month.month') as $monthCell) {
            $expectedNulls = [];

            foreach ($monthCell->drillDown('customerGender') as $cell) {
                $expectedNulls[] = $cell->getMeasures()->get('price')?->getValue() === null;
            }

            if (\in_array(true, $expectedNulls, true)) {
                $cube = $monthCell;

                break;
            }
        }

        $this->assertNotNull($cube, 'Fixture has no missing value');

        $chart = static::getContainer()
            ->get(ChartGenerator::class)
            ->createChart(
                cube: $cube,
                dimensions: ['customerGender'],
                measures: ['price'],
                chartType: ChartType::Pie,
            );

        /** @var array{datasets:list<array{data:list<int|float|null>}>} */
        $data = $chart->getData();

        $this->assertSame($expectedNulls, array_map(
            static fn(int|float|null $value): bool => $value === null,
            $data['datasets'][0]['data'],
        ));
    }

    public function testPieChartWithInvalidMeasure(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->createChart(['customerCountry'], ['invalid'], ChartType::Pie);
    }

    /**
     * @return iterable<string,array{list<string>,list<string>,ChartType}>
     */
    public static function provideUnsupportedData(): iterable
    {
        yield 'no dimension' => [[], ['count'], ChartType::Bar];
        yield 'no measure' => [['customerCountry'], [], ChartType::Bar];
        yield 'stacked bar with one dimension' => [['customerCountry'], ['count'], ChartType::StackedBar];
        yield 'grouped bar with one dimension' => [['customerCountry'], ['count'], ChartType::GroupedBar];
    }

    /**
     * @param list<string> $dimensions
     * @param list<string> $measures
     */
    #[DataProvider('provideUnsupportedData')]
    public function testUnsupportedData(
        array $dimensions,
        array $measures,
        ChartType $chartType,
    ): void {
        $this->expectException(UnsupportedData::class);

        $this->createChart($dimensions, $measures, $chartType);
    }

    private function getYTitle(Chart $chart): ?string
    {
        /** @var array{scales?:array{y?:array{title?:array{text?:string}}}} */
        $options = $chart->getOptions();

        return $options['scales']['y']['title']['text'] ?? null;
    }
}
