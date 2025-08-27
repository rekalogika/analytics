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
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        try {
            if ($chartType === ChartType::Auto) {
                return $this->createAutoChart($result, $measures);
            }

            if ($chartType === ChartType::Bar) {
                return $this->createBarChart($result, $measures);
            }

            if ($chartType === ChartType::Line) {
                return $this->createLineChart($result, $measures);
            }

            if ($chartType === ChartType::StackedBar) {
                return $this->createGroupedBarChart($result, $measures, 'stackedBar');
            }

            if ($chartType === ChartType::GroupedBar) {
                return $this->createGroupedBarChart($result, $measures, 'groupedBar');
            }

            if ($chartType === ChartType::Pie) {
                if (\count($measures) !== 1) {
                    throw new UnsupportedData('Exactly one measure is required for pie chart');
                }

                return $this->createPieChart($result, $measures[0]);
            }
        } catch (EmptyResultException $e) {
            throw new UnsupportedData('Result is empty', previous: $e);
        } catch (\Throwable $e) {
            throw FrontendWrapperException::selectiveWrap($e);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    /**
     * @param non-empty-list<string> $measures
     */
    private function createAutoChart(Result $result, array $measures): Chart
    {
        $row = $result->getTable()->first();

        if ($row === null) {
            throw new UnsupportedData('Result is empty');
        }

        $tuple = $row->getTuple();

        if (\count($tuple) === 1) {
            if ($this->isFirstDimensionSequential($result)) {
                return $this->createLineChart($result, $measures);
            } else {
                return $this->createBarChart($result, $measures);
            }
        } elseif (\count($tuple) === 2) {
            // @todo auto detect best chart type
            return $this->createGroupedBarChart($result, $measures, 'groupedBar');
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function isFirstDimensionSequential(Result $result): bool
    {
        $firstDimension = $result->getDimensionality()[0] ?? null;

        if ($firstDimension === null) {
            throw new UnsupportedData('No dimensions found');
        }

        foreach ($result->getCube()->drillDown($firstDimension) as $child) {
            /** @psalm-suppress MixedAssignment */
            $member = $child->getTuple()->get($firstDimension)?->getMember();

            if ($member === null) {
                throw new UnsupportedData('Expected a member');
            }

            if (!$member instanceof SequenceMember) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param non-empty-list<string> $measures
     */
    private function createBarChart(Result $result, array $measures): Chart
    {
        return $this->createBarOrLineChart($result, $measures, Chart::TYPE_BAR);
    }

    /**
     * @param non-empty-list<string> $measures
     */
    private function createLineChart(Result $result, array $measures): Chart
    {
        $row = $result->getTable()->first();

        if ($row === null) {
            throw new UnsupportedData('Result is empty');
        }

        $tuple = $row->getTuple();

        if (\count($tuple) === 1) {
            return $this->createBarOrLineChart($result, $measures, Chart::TYPE_LINE);
        } elseif (\count($tuple) === 2) {
            return $this->createGroupedBarChart($result, $measures, 'multiLine');
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    /**
     * @param non-empty-list<string> $measures
     * @param Chart::TYPE_LINE|Chart::TYPE_BAR $type
     */
    private function createBarOrLineChart(Result $result, array $measures, string $type): Chart
    {
        $configuration = $this->configurationFactory->createChartConfiguration();
        $measuresObject = $result->getCube()->getMeasures();

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

        $cube = $result->getCube();
        $dimensions = $result->getDimensionality();
        $firstDimension = $dimensions[0] ?? throw new UnsupportedData('No dimensions found');

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
    private function createGroupedBarChart(Result $result, array $measures, string $type): Chart
    {
        $configuration = $this->configurationFactory->createChartConfiguration();
        $measure = $result->getCube()->getMeasures()->first();

        if ($measure === null) {
            throw new UnexpectedValueException('Measures not found');
        }

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;
        $legendTitle = null;

        $firstDimensionName = $result->getDimensionality()[0] ?? null;
        $secondDimensionName = $result->getDimensionality()[1] ?? null;

        if ($firstDimensionName === null || $secondDimensionName === null) {
            throw new UnexpectedValueException('At least two dimensions are required');
        }

        // Populate data.
        foreach ($result->getCube()->drillDown($firstDimensionName) as $firstCell) {
            $firstDimension = $firstCell->getTuple()->get($firstDimensionName);

            if ($firstDimension === null) {
                throw new UnexpectedValueException('Unable to get first dimension');
            }

            $labels[] = $this->stringifier->toString($firstDimension->getDisplayMember());

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($firstDimension->getLabel());
            }

            foreach ($firstCell->drillDown($secondDimensionName) as $secondCell) {
                $secondDimension = $secondCell->getTuple()->get($secondDimensionName);
                $signature = $this->getSignature($secondDimension);

                if (!isset($dataSets[$signature])) {
                    $dataSets[$signature] = $configuration->createChartElementConfiguration()->toArray();
                    $dataSets[$signature]['data'] = [];
                }

                if ($secondDimension === null) {
                    // Should never happen.
                    throw new UnexpectedValueException('Unable to get second dimension');
                }

                if (!isset($dataSets[$signature]['label'])) {
                    $dataSets[$signature]['label'] = $this->stringifier->toString($secondDimension->getDisplayMember() ?? null);
                }

                if ($legendTitle === null) {
                    $legendTitle = $this->stringifier->toString($secondDimension->getLabel());
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
                            $this->stringifier->toString($secondDimension->getDisplayMember()),
                            $this->stringifier->toString($unit),
                        );
                    } else {
                        $yTitle = $this->stringifier->toString($secondDimension->getDisplayMember());
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

    private function createPieChart(Result $result, string $measureName): Chart
    {
        $configuration = $this->configurationFactory->createChartConfiguration();
        $measures = $result->getTable()->first()?->getMeasures();

        if ($measures === null) {
            throw new UnsupportedData('Measures not found');
        }

        // Populate labels.

        $measure = $measures->get($measureName);

        $labels = [];
        $dataSet = [];

        $dataSet['label'] = $this->stringifier->toString($measure?->getLabel());
        $dataSet['data'] = [];
        $dataSet['backgroundColor'] = [];
        $dataSet['hoverOffset'] = 4;

        $title = null;

        // Populate data.

        foreach ($result->getTable() as $row) {
            $tuple = $row->getTuple();

            if (\count($tuple) !== 1) {
                throw new UnsupportedData('Expected only one member');
            }

            $dimension = $tuple->getByIndex(0);

            if ($dimension === null) {
                throw new UnsupportedData('Expected only one member');
            }

            // Get label.

            if ($title === null) {
                $title = $this->stringifier->toString($dimension->getLabel());
            }

            // Get value.

            $labels[] = $this->stringifier->toString($dimension->getDisplayMember());

            $measures = $row->getMeasures();
            $measure = $measures->get($measureName);

            $dataSet['data'][] = $this->numberifier->toNumber($measure?->getValue());

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
