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

namespace Rekalogika\Analytics\Bundle\Chart\Implementation;

use Rekalogika\Analytics\Bundle\Chart\AnalyticsChartBuilder;
use Rekalogika\Analytics\Bundle\Chart\ChartType;
use Rekalogika\Analytics\Bundle\Chart\UnsupportedData;
use Rekalogika\Analytics\Bundle\Formatter\Numberifier;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Contracts\Model\Bin;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Core\Exception\EmptyResultException;
use Rekalogika\Analytics\Core\Exception\UnexpectedValueException;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class DefaultAnalyticsChartBuilder implements AnalyticsChartBuilder
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private ChartBuilderInterface $chartBuilder,
        private Stringifier $stringifier,
        private ChartConfiguration $configuration,
        private Numberifier $numberifier,
    ) {}

    #[\Override]
    public function createChart(
        Result $result,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        try {
            if ($chartType === ChartType::Auto) {
                return $this->createAutoChart($result);
            }

            if ($chartType === ChartType::Bar) {
                return $this->createBarChart($result);
            }

            if ($chartType === ChartType::Line) {
                return $this->createLineChart($result);
            }

            if ($chartType === ChartType::StackedBar) {
                return $this->createGroupedBarChart($result, 'stackedBar');
            }

            if ($chartType === ChartType::GroupedBar) {
                return $this->createGroupedBarChart($result, 'groupedBar');
            }

            if ($chartType === ChartType::Pie) {
                return $this->createPieChart($result);
            }
        } catch (EmptyResultException $e) {
            throw new UnsupportedData('Result is empty', previous: $e);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function createAutoChart(Result $result): Chart
    {
        $tuple = $result->getTable()->getRowPrototype();

        if (\count($tuple) === 1) {
            if ($this->isFirstDimensionSequential($result)) {
                return $this->createLineChart($result);
            } else {
                return $this->createBarChart($result);
            }
        } elseif (\count($tuple) === 2) {
            // @todo auto detect best chart type
            return $this->createGroupedBarChart($result, 'groupedBar');
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function isFirstDimensionSequential(Result $result): bool
    {
        $tree = $result->getTree();

        $lastRawMember = null;
        $direction = null;

        foreach ($tree as $child) {
            /** @psalm-suppress MixedAssignment */
            $rawMember = $child->getRawMember();

            if (!$rawMember instanceof Bin) {
                return false;
            }

            $class = $rawMember::class;

            if (
                $lastRawMember !== null
                && $direction !== null
                && $class::compare($lastRawMember, $rawMember) !== $direction
            ) {
                return false;
            }

            if ($direction === null && $lastRawMember !== null) {
                $direction = $class::compare($lastRawMember, $rawMember);
            }

            $lastRawMember = $rawMember;
        }

        return true;
    }

    private function createBarChart(Result $result): Chart
    {
        return $this->createBarOrLineChart($result, Chart::TYPE_BAR);
    }

    private function createLineChart(Result $result): Chart
    {
        $tuple = $result->getTable()->getRowPrototype();

        if (\count($tuple) === 1) {
            return $this->createBarOrLineChart($result, Chart::TYPE_LINE);
        } elseif (\count($tuple) === 2) {
            return $this->createGroupedBarChart($result, 'multiLine');
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    /**
     * @param Chart::TYPE_LINE|Chart::TYPE_BAR $type
     */
    private function createBarOrLineChart(Result $result, string $type): Chart
    {
        $colorDispenser = $this->createColorDispenser();
        $measures = $result->getTable()->getRowPrototype()->getMeasures();
        $selectedMeasures = $this->selectMeasures($measures);
        $numMeasures = \count($selectedMeasures);

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;

        // populate labels

        foreach ($selectedMeasures as $name) {
            $measure = $measures->getByName($name)
                ?? throw new UnexpectedValueException(\sprintf(
                    'Measure "%s" not found',
                    $name,
                ));

            $dataSets[$name]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$name]['data'] = [];

            $color = $colorDispenser->dispenseColor();
            $dataSets[$name]['backgroundColor'] = $color . $this->configuration->areaTransparency;
            $dataSets[$name]['borderColor'] = $color;
            $dataSets[$name]['borderWidth'] = $this->configuration->areaBorderWidth;

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

        // populate data

        foreach ($result->getTable() as $row) {
            if (\count($row) !== 1) {
                throw new UnsupportedData('Expected only one member');
            }

            $dimension = $row->getByIndex(0);

            if ($dimension === null) {
                throw new UnsupportedData('Expected only one member');
            }

            // get label

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($dimension->getLabel());
            }

            // get value

            $labels[] = $this->stringifier->toString($dimension->getDisplayMember());

            $measures = $row->getMeasures();

            foreach ($selectedMeasures as $name) {
                $measure = $measures->getByName($name);

                /** @todo implement proper gap filling */

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
                'font' => $this->configuration->labelFont,
            ];
        }

        // ytitle

        if ($yTitle === null) {
            $yTitle = [
                'display' => false,
            ];
        } else {
            $yTitle = [
                'display' => true,
                'text' => $yTitle,
                'font' => $this->configuration->labelFont,
            ];
        }

        // legend

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
    private function createGroupedBarChart(Result $result, string $type): Chart
    {
        $colorDispenser = $this->createColorDispenser();
        $measure = $result->getTable()->getRowPrototype()->getMeasures()->getByIndex(0);

        if ($measure === null) {
            throw new UnsupportedData('Measures not found');
        }

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;
        $legendTitle = null;

        // collect second dimension

        $secondDimensions = [];

        foreach ($result->getTable() as $row) {
            $secondDimension = $row->getByIndex(1);

            if ($secondDimension === null) {
                throw new UnsupportedData('Expected a second dimension');
            }

            /** @psalm-suppress MixedAssignment */
            $secondDimensions[] = $secondDimension->getMember();
        }

        $secondDimensions = array_unique($secondDimensions, SORT_REGULAR);

        // populate data

        foreach ($result->getTree() as $node) {
            $labels[] = $this->stringifier->toString($node->getDisplayMember());

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($node->getLabel());
            }

            /** @psalm-suppress MixedAssignment */
            foreach ($secondDimensions as $dimension2) {
                $node2 = $node->traverse($dimension2);

                $signature = $this->getSignature($dimension2);

                if (!isset($dataSets[$signature]['backgroundColor'])) {
                    $color = $colorDispenser->dispenseColor();
                    $dataSets[$signature]['backgroundColor'] = $color . $this->configuration->areaTransparency;
                    $dataSets[$signature]['borderColor'] = $color;
                    $dataSets[$signature]['borderWidth'] = $this->configuration->areaBorderWidth;
                }

                if ($node2 === null) {
                    $dataSets[$signature]['data'][] = 0;

                    continue;
                }

                if (!isset($dataSets[$signature]['label'])) {
                    $dataSets[$signature]['label'] = $this->stringifier->toString($node2->getDisplayMember() ?? null);
                }

                if ($legendTitle === null) {
                    $legendTitle = $this->stringifier->toString($node2->getLabel());
                }

                $children = iterator_to_array($node2, false);
                $dimension = $children[0];
                $measure = $dimension->getMeasure();

                if ($measure === null) {
                    throw new UnsupportedData('Measures not found');
                }

                $dataSets[$signature]['data'][] = $this->numberifier->toNumber($measure->getValue());

                if ($yTitle === null) {
                    $unit = $measure->getUnit();

                    if ($unit !== null) {
                        $yTitle = \sprintf(
                            '%s - %s',
                            $this->stringifier->toString($dimension->getDisplayMember()),
                            $this->stringifier->toString($unit),
                        );
                    } else {
                        $yTitle = $this->stringifier->toString($dimension->getDisplayMember());
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

        // xtitle

        if ($xTitle === null) {
            $xTitle = [
                'display' => false,
            ];
        } else {
            $xTitle = [
                'display' => true,
                'text' => $xTitle,
                'font' => $this->configuration->labelFont,
            ];
        }

        // ytitle

        if ($yTitle === null) {
            $yTitle = [
                'display' => false,
            ];
        } else {
            $yTitle = [
                'display' => true,
                'text' => $yTitle,
                'font' => $this->configuration->labelFont,
            ];
        }

        // legend title

        if ($legendTitle === null) {
            $legendTitle = [
                'display' => false,
            ];
        } else {
            $legendTitle = [
                'display' => true,
                'text' => $legendTitle,
                'font' => $this->configuration->labelFont,
            ];
        }

        // legend

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
            'plugins' => [
                'legend' => [
                    'title' => $legendTitle,
                    'labels' => $legend,
                ],
                'title' => [
                    'display' => false,
                ],
                'scales' => $scales,
            ],

        ]);

        return $chart;
    }

    private function createPieChart(Result $result): Chart
    {
        $colorDispenser = $this->createColorDispenser();
        $measures = $result->getTable()->getRowPrototype()->getMeasures();
        $selectedMeasures = $this->selectMeasures($measures);
        $numMeasures = \count($selectedMeasures);

        if ($numMeasures !== 1) {
            throw new UnsupportedData('Only one measure is supported');
        }

        // populate labels

        $name = $selectedMeasures[0];
        $measure = $measures->getByName($name);

        $labels = [];
        $dataSet = [];

        $dataSet['label'] = $this->stringifier->toString($measure?->getLabel());
        $dataSet['data'] = [];
        $dataSet['backgroundColor'] = [];
        $dataSet['hoverOffset'] = 4;

        $title = null;

        // populate data

        foreach ($result->getTable() as $row) {
            if (\count($row) !== 1) {
                throw new UnsupportedData('Expected only one member');
            }

            $dimension = $row->getByIndex(0);

            if ($dimension === null) {
                throw new UnsupportedData('Expected only one member');
            }

            // get label

            if ($title === null) {
                $title = $this->stringifier->toString($dimension->getLabel());
            }

            // get value

            $labels[] = $this->stringifier->toString($dimension->getDisplayMember());

            $measures = $row->getMeasures();
            $measure = $measures->getByName($name);

            $dataSet['data'][] = $this->numberifier->toNumber($measure?->getValue());

            // color

            $color = $colorDispenser->dispenseColor();
            $dataSet['backgroundColor'][] = $color . $this->configuration->areaTransparency;
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
     * @return list<string>
     */
    private function selectMeasures(Measures $measures): array
    {
        $selectedMeasures = [];
        $selectedUnit = null;

        foreach ($measures as $measure) {
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

    private function createColorDispenser(): ColorDispenser
    {
        return new ColorDispenser();
    }
}
