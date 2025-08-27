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
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\Result;
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
        Result $result,
        array $dimensions,
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        $cube = $result->getCube();

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
        /** @psalm-suppress MixedAssignment */
        $member = $cube
            ->getTuple()
            ->get($dimension)
            ?->getMember();

        return $member instanceof SequenceMember;
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

        $selectedMeasures = $this->selectMeasures($measuresObject, $measures);
        $numMeasures = \count($selectedMeasures);

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;

        // Populate labels.

        foreach ($selectedMeasures as $name) {
            $measure = $measuresObject->get($name)
                ?? throw new UnexpectedValueException(\sprintf(
                    'Measure "%s" not found',
                    $name,
                ));

            $dataSets[$name] = $configuration->createChartElementConfiguration()->toArray();
            $dataSets[$name]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$name]['data'] = [];

            if ($yTitle === null) {
                $unit = $measure->getUnit();

                if ($unit === null) {
                    if ($numMeasures === 1) {
                        $yTitle = $this->stringifier->toString($measure->getLabel());
                    }
                } elseif ($numMeasures === 1) {
                    $yTitle = \sprintf(
                        '%s - %s',
                        $this->stringifier->toString($measure->getLabel()),
                        $this->stringifier->toString($unit),
                    );
                } else {
                    $yTitle = $this->stringifier->toString($unit);
                }
            }
        }

        // Populate data.
        $firstDimension = $dimensions[0];

        foreach ($cube->drillDown($firstDimension) as $row) {
            $tuple = $row->getTuple();
            $dimension = $tuple->get($firstDimension);

            if ($dimension === null) {
                throw new UnsupportedData('Expected only one member');
            }

            // Get label.

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($dimension->getLabel());
            }

            // Get value.

            $labels[] = $this->stringifier->toString($dimension->getDisplayMember());

            $measures = $row->getMeasures();

            foreach ($selectedMeasures as $name) {
                $measure = $measures->get($name);

                /** @psalm-suppress MixedAssignment */
                $value = $measure?->getValue() ?? 0;

                $dataSets[$name]['data'][] = $this->numberifier->toNumber($value);

                // $value = $measure?->getValue();
                // $dataSets[$name]['data'][] = $value;
                // $value === null
                //     ? null
                //     : $this->numberifier->toNumber($value);
            }
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
            'scales' => [
                'x' => [
                    'title' => $xTitle,
                ],
                'y' => [
                    'title' => $yTitle,
                ],
            ],
            'spanGaps' => true,
        ]);

        return $chart;
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
        $measure = $cube->getMeasures()->get($measure);

        if ($measure === null) {
            throw new UnexpectedValueException('Measures not found');
        }

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;
        $legendTitle = null;

        // Populate data.
        foreach ($cube->drillDown($firstDimension) as $firstCell) {
            $firstDimensionObject = $firstCell->getTuple()->get($firstDimension);

            if ($firstDimensionObject === null) {
                throw new UnexpectedValueException('Unable to get first dimension');
            }

            $labels[] = $this->stringifier->toString($firstDimensionObject->getDisplayMember());

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($firstDimensionObject->getLabel());
            }

            foreach ($firstCell->drillDown($secondDimension) as $secondCell) {
                $secondDimensionObject = $secondCell->getTuple()->get($secondDimension);
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
                    $dataSets[$signature]['label'] = $this->stringifier->toString($secondDimensionObject->getDisplayMember() ?? null);
                }

                if ($legendTitle === null) {
                    $legendTitle = $this->stringifier->toString($secondDimensionObject->getLabel());
                }

                $measure = $secondCell->getMeasures()->first();

                if ($measure === null) {
                    throw new UnexpectedValueException('Measure not found');
                }

                $dataSets[$signature]['data'][] = $this->numberifier->toNumber($measure->getValue());

                if ($yTitle === null) {
                    $unit = $measure->getUnit();

                    if ($unit !== null) {
                        $yTitle = \sprintf(
                            '%s - %s',
                            $this->stringifier->toString($secondDimensionObject->getDisplayMember()),
                            $this->stringifier->toString($unit),
                        );
                    } else {
                        $yTitle = $this->stringifier->toString($secondDimensionObject->getDisplayMember());
                    }
                }
            }
        }

        if ($type === 'multiLine') {
            $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        } else {
            $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => array_values($dataSets),
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
                'legend' => [
                    'title' => $legendTitle,
                    'labels' => $legend,
                ],
                'title' => [
                    'display' => false,
                ],
            ],

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
            $tuple = $child->getTuple();
            $dimensionObject = $child->getTuple()->get($dimension);

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
     * @param list<string> $userSuppliedMeasures
     * @return list<string>
     */
    private function selectMeasures(Measures $measures, array $userSuppliedMeasures): array
    {
        $selectedMeasures = [];
        $selectedUnit = null;

        foreach ($measures as $measure) {
            if (!\in_array($measure->getName(), $userSuppliedMeasures, true)) {
                continue;
            }

            $unit = $measure->getUnit();

            if ($selectedMeasures === [] && $unit === null) {
                return [$measure->getName()];
            }

            if ($selectedUnit === null) {
                $selectedUnit = $unit;
            }

            if (
                $selectedUnit !== null &&
                $selectedUnit->getSignature() === $unit?->getSignature()
            ) {
                $selectedMeasures[] = $measure->getName();
            }
        }

        return $selectedMeasures;
    }

    private function getSignature(mixed $variable): string
    {
        if (\is_object($variable)) {
            return (string) spl_object_id($variable);
        }

        return hash('xxh128', serialize($variable));
    }
}
