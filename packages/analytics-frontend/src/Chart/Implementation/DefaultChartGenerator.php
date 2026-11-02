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

namespace Rekalogika\Analytics\Frontend\Chart\Implementation;

use Rekalogika\Analytics\Contracts\Exception\EmptyResultException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Model\SequenceMember;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\Measure;
use Rekalogika\Analytics\Contracts\Result\Unit;
use Rekalogika\Analytics\Frontend\Chart\ChartGenerator;
use Rekalogika\Analytics\Frontend\Chart\ChartType;
use Rekalogika\Analytics\Frontend\Chart\Configuration\ChartConfigurationFactory;
use Rekalogika\Analytics\Frontend\Chart\UnsupportedData;
use Rekalogika\Analytics\Frontend\Exception\FrontendWrapperException;
use Rekalogika\Analytics\Frontend\Formatter\Numberifier;
use Rekalogika\Analytics\Frontend\Formatter\Stringifier;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class DefaultChartGenerator implements ChartGenerator
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private ChartBuilderInterface $chartBuilder,
        private Stringifier $stringifier,
        private ChartConfigurationFactory $configurationFactory,
        private Numberifier $numberifier,
    ) {}

    #[\Override]
    public function createChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        if ($dimensions === []) {
            throw new UnsupportedData('At least one dimension is required');
        }

        if ($measures === []) {
            throw new UnsupportedData('At least one measure is required');
        }

        if (
            \count($dimensions) < 2
            && ($chartType === ChartType::StackedBar || $chartType === ChartType::GroupedBar)
        ) {
            throw new UnsupportedData('At least two dimensions are required');
        }

        try {
            if ($chartType === ChartType::Auto) {
                return $this->createAutoChart(
                    cube: $cube,
                    dimensions: $dimensions,
                    measures: $measures,
                );
            }

            if ($chartType === ChartType::Bar) {
                return $this->createBarChart(
                    cube: $cube,
                    dimensions: $dimensions,
                    measures: $measures,
                );
            }

            if ($chartType === ChartType::Line) {
                return $this->createLineChart(
                    cube: $cube,
                    dimensions: $dimensions,
                    measures: $measures,
                );
            }

            if ($chartType === ChartType::StackedBar) {
                return $this->createGroupedBarChart(
                    cube: $cube,
                    firstDimension: $dimensions[0],
                    secondDimension: $dimensions[1],
                    measure: $measures[0],
                    type: 'stackedBar',
                );
            }

            if ($chartType === ChartType::GroupedBar) {
                return $this->createGroupedBarChart(
                    cube: $cube,
                    firstDimension: $dimensions[0],
                    secondDimension: $dimensions[1],
                    measure: $measures[0],
                    type: 'groupedBar',
                );
            }

            if ($chartType === ChartType::Pie) {
                return $this->createPieChart(
                    cube: $cube,
                    dimension: $dimensions[0],
                    measure: $measures[0],
                );
            }
        } catch (EmptyResultException $e) {
            throw new UnsupportedData('Result is empty', previous: $e);
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    /**
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     */
    private function createAutoChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
    ): Chart {
        if (\count($dimensions) === 1) {
            if ($this->isDimensionSequential($cube, $dimensions[0])) {
                return $this->createLineChart(
                    cube: $cube,
                    dimensions: $dimensions,
                    measures: $measures,
                );
            } else {
                return $this->createBarChart(
                    cube: $cube,
                    dimensions: $dimensions,
                    measures: $measures,
                );
            }
        } elseif (\count($dimensions) >= 2) {
            // @todo auto detect best chart type
            return $this->createGroupedBarChart(
                cube: $cube,
                firstDimension: $dimensions[0],
                secondDimension: $dimensions[1],
                measure: $measures[0],
                type: 'groupedBar',
            );
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function isDimensionSequential(CubeCell $cube, string $dimension): bool
    {
        // The given cell does not have the dimension in its coordinates, look
        // at the members of its children instead. The null member does not
        // tell us anything, skip it.
        foreach ($cube->drillDown($dimension) as $cell) {
            /** @psalm-suppress MixedAssignment */
            $member = $cell
                ->getCoordinates()
                ->get($dimension)
                ?->getMember();

            if ($member === null) {
                continue;
            }

            return $member instanceof SequenceMember;
        }

        return false;
    }

    /**
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     */
    private function createBarChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
    ): Chart {
        return $this->createBarOrLineChart(
            cube: $cube,
            dimensions: $dimensions,
            measures: $measures,
            type: Chart::TYPE_BAR,
        );
    }

    /**
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     */
    private function createLineChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
    ): Chart {
        if (\count($dimensions) === 1) {
            return $this->createBarOrLineChart(
                cube: $cube,
                dimensions: $dimensions,
                measures: $measures,
                type: Chart::TYPE_LINE,
            );
        } elseif (\count($dimensions) > 1) {
            return $this->createGroupedBarChart(
                cube: $cube,
                firstDimension: $dimensions[0],
                secondDimension: $dimensions[1],
                measure: $measures[0],
                type: 'multiLine',
            );
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    /**
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     * @param Chart::TYPE_LINE|Chart::TYPE_BAR $type
     */
    private function createBarOrLineChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
        string $type,
    ): Chart {
        $configuration = $this->configurationFactory->createChartConfiguration();
        $measuresObject = $cube->getMeasures();

        $labels = [];
        $dataSets = [];

        $xTitle = null;

        // Measures with different units cannot share an axis, so each unit
        // gets its own y axis.

        /** @var array<string,array{id:string,unit:?Unit,measures:non-empty-list<Measure>}> */
        $axes = [];

        // Populate labels.

        foreach ($measures as $name) {
            $measure = $measuresObject->get($name)
                ?? throw new UnexpectedValueException(\sprintf(
                    'Measure "%s" not found',
                    $name,
                ));

            $unit = $measure->getUnit();
            $unitSignature = $unit === null ? '' : 'unit:' . $unit->getSignature();

            if (isset($axes[$unitSignature])) {
                $axes[$unitSignature]['measures'][] = $measure;
            } else {
                $axes[$unitSignature] = [
                    'id' => $axes === [] ? 'y' : 'y' . \count($axes),
                    'unit' => $unit,
                    'measures' => [$measure],
                ];
            }

            $dataSets[$name] = $configuration->createChartElementConfiguration()->toArray();
            $dataSets[$name]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$name]['data'] = [];
            $dataSets[$name]['yAxisID'] = $axes[$unitSignature]['id'];
        }

        $numMeasures = \count($dataSets);

        // Populate data.
        $firstDimension = $dimensions[0];

        foreach ($cube->drillDown($firstDimension) as $row) {
            $coordinates = $row->getCoordinates();
            $dimension = $coordinates->get($firstDimension);

            if ($dimension === null) {
                throw new UnsupportedData('Expected only one member');
            }

            // Get label.

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($dimension->getLabel());
            }

            // Get value.

            $labels[] = $this->stringifier->toString($dimension->getDisplayMember());

            $rowMeasures = $row->getMeasures();

            foreach (array_keys($dataSets) as $name) {
                $measure = $rowMeasures->get($name);

                $dataSets[$name]['data'][] = $this->toChartValue($measure?->getValue());
            }
        }

        if ($labels === []) {
            throw new UnsupportedData('No data available for the chart');
        }

        $chart = $this->chartBuilder->createChart($type);

        $chart->setData([
            'labels' => $labels,
            'datasets' => array_values($dataSets),
        ]);

        // xtitle

        if ($xTitle === null) {
            $xTitle = [
                'display' => false,
            ];
        } else {
            $xTitle = [
                'display' => true,
                'text' => $xTitle,
                'font' => $configuration->getChartLabelFont()->toArray(),
            ];
        }

        // Y axes.

        $scales = [
            'x' => [
                'title' => $xTitle,
            ],
        ];

        $i = 0;

        foreach ($axes as $axis) {
            $yTitle = $this->getYAxisTitle($axis['unit'], $axis['measures']);

            if ($yTitle === null) {
                $scale = [
                    'title' => [
                        'display' => false,
                    ],
                ];
            } else {
                $scale = [
                    'title' => [
                        'display' => true,
                        'text' => $yTitle,
                        'font' => $configuration->getChartLabelFont()->toArray(),
                    ],
                ];
            }

            // The additional axes alternate between the right and left side,
            // and do not draw their grid lines over the first axis.

            if ($i > 0) {
                $scale['position'] = $i % 2 === 1 ? 'right' : 'left';
                $scale['grid'] = [
                    'drawOnChartArea' => false,
                ];
            }

            $scales[$axis['id']] = $scale;
            $i++;
        }

        // Legend.

        if ($numMeasures > 1) {
            $legend = [
                'display' => true,
                'position' => 'top',
            ];
        } else {
            $legend = [
                'display' => false,
            ];
        }

        $chart->setOptions([
            'responsive' => true,
            'locale' => $this->localeSwitcher->getLocale(),
            'plugins' => [
                'legend' => $legend,
                'title' => [
                    'display' => false,
                ],
            ],
            'scales' => $scales,
            'spanGaps' => true,
        ]);

        return $chart;
    }

    /**
     * @param non-empty-list<Measure> $measures Measures sharing the axis
     */
    private function getYAxisTitle(?Unit $unit, array $measures): ?string
    {
        if (\count($measures) === 1) {
            $label = $this->stringifier->toString($measures[0]->getLabel());

            if ($unit === null) {
                return $label;
            }

            return \sprintf(
                '%s - %s',
                $label,
                $this->stringifier->toString($unit),
            );
        }

        if ($unit === null) {
            return null;
        }

        return $this->stringifier->toString($unit);
    }

    /**
     * @param 'stackedBar'|'groupedBar'|'multiLine' $type
     */
    private function createGroupedBarChart(
        CubeCell $cube,
        string $firstDimension,
        string $secondDimension,
        string $measure,
        string $type,
    ): Chart {
        $configuration = $this->configurationFactory->createChartConfiguration();

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;
        $legendTitle = null;

        // Populate data.
        foreach ($cube->drillDown($firstDimension) as $firstCell) {
            $firstDimensionObject = $firstCell->getCoordinates()->get($firstDimension);

            if ($firstDimensionObject === null) {
                throw new UnexpectedValueException('Unable to get first dimension');
            }

            $labels[] = $this->stringifier->toString($firstDimensionObject->getDisplayMember());

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($firstDimensionObject->getLabel());
            }

            foreach ($firstCell->drillDown($secondDimension) as $secondCell) {
                $secondDimensionObject = $secondCell->getCoordinates()->get($secondDimension);
                $signature = $this->getSignature($secondDimensionObject);

                if (!isset($dataSets[$signature])) {
                    $dataSets[$signature] = $configuration->createChartElementConfiguration()->toArray();
                    $dataSets[$signature]['data'] = [];
                }

                if ($secondDimensionObject === null) {
                    // Should never happen.
                    throw new UnexpectedValueException('Unable to get second dimension');
                }

                if (!isset($dataSets[$signature]['label'])) {
                    $dataSets[$signature]['label'] = $this->stringifier->toString($secondDimensionObject->getDisplayMember());
                }

                if ($legendTitle === null) {
                    $legendTitle = $this->stringifier->toString($secondDimensionObject->getLabel());
                }

                $measureObject = $secondCell->getMeasures()->get($measure);

                if ($measureObject === null) {
                    throw new UnexpectedValueException('Measure not found');
                }

                $dataSets[$signature]['data'][] = $this->toChartValue($measureObject->getValue());

                if ($yTitle === null) {
                    $unit = $measureObject->getUnit();

                    if ($unit !== null) {
                        $yTitle = \sprintf(
                            '%s - %s',
                            $this->stringifier->toString($measureObject->getLabel()),
                            $this->stringifier->toString($unit),
                        );
                    } else {
                        $yTitle = $this->stringifier->toString($measureObject->getLabel());
                    }
                }
            }
        }

        $dataSets = array_values($dataSets);

        if ($dataSets === []) {
            throw new UnsupportedData('No data available for the chart');
        }

        if ($type === 'multiLine') {
            $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        } else {
            $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => $dataSets,
        ]);

        // X title.

        if ($xTitle === null) {
            $xTitle = [
                'display' => false,
            ];
        } else {
            $xTitle = [
                'display' => true,
                'text' => $xTitle,
                'font' => $configuration->getChartLabelFont()->toArray(),
            ];
        }

        // Y title.

        if ($yTitle === null) {
            $yTitle = [
                'display' => false,
            ];
        } else {
            $yTitle = [
                'display' => true,
                'text' => $yTitle,
                'font' => $configuration->getChartLabelFont()->toArray(),
            ];
        }

        // Legend title.

        if ($legendTitle === null) {
            $legendTitle = [
                'display' => false,
            ];
        } else {
            $legendTitle = [
                'display' => true,
                'text' => $legendTitle,
                'font' => $configuration->getChartLabelFont()->toArray(),
            ];
        }

        // Legend.

        $legend = [
            'display' => true,
            'position' => 'top',
            'title' => $legendTitle,
        ];

        $scales = [
            'x' => [
                'title' => $xTitle,
            ],
            'y' => [
                'title' => $yTitle,
            ],
        ];

        if ($type === 'stackedBar') {
            $scales['x']['stacked'] = true;
            $scales['y']['stacked'] = true;
        }

        $chart->setOptions([
            'responsive' => true,
            'locale' => $this->localeSwitcher->getLocale(),
            'scales' => $scales,
            'plugins' => [
                'legend' => $legend,
                'title' => [
                    'display' => false,
                ],
            ],
            'spanGaps' => true,
        ]);

        return $chart;
    }

    private function createPieChart(
        CubeCell $cube,
        string $dimension,
        string $measure,
    ): Chart {
        $configuration = $this->configurationFactory->createChartConfiguration();

        // Populate labels.

        $measureObject = $cube->getMeasures()->get($measure);

        $labels = [];
        $dataSet = [];

        $dataSet['label'] = $this->stringifier->toString($measureObject?->getLabel());
        $dataSet['data'] = [];
        $dataSet['backgroundColor'] = [];
        $dataSet['hoverOffset'] = 4;

        $title = null;

        // Populate data.

        foreach ($cube->drillDown($dimension) as $child) {
            $dimensionObject = $child->getCoordinates()->get($dimension);

            if ($dimensionObject === null) {
                throw new UnexpectedValueException('Expected dimension');
            }

            // Get label.

            if ($title === null) {
                $title = $this->stringifier->toString($dimensionObject->getLabel());
            }

            // Get value.

            $labels[] = $this->stringifier->toString($dimensionObject->getDisplayMember());
            $measureObject = $child->getMeasures()->get($measure);
            $dataSet['data'][] = $this->numberifier->toNumber($measureObject?->getValue());

            // Color.

            $color = $configuration->createChartElementConfiguration()->getAreaColor();
            $dataSet['backgroundColor'][] = $color;
        }

        if ($dataSet['data'] === []) {
            throw new UnsupportedData('No data available for the chart');
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [$dataSet],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'locale' => $this->localeSwitcher->getLocale(),
        ]);

        return $chart;
    }

    /**
     * A missing value is emitted as null, not zero, so that the chart shows a
     * gap instead of a dip to zero.
     */
    private function toChartValue(mixed $value): int|float|null
    {
        if ($value === null) {
            return null;
        }

        return $this->numberifier->toNumber($value);
    }

    private function getSignature(mixed $variable): string
    {
        if (\is_object($variable)) {
            return (string) spl_object_id($variable);
        }

        return hash('xxh128', serialize($variable));
    }
}
