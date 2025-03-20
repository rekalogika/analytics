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
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Contracts\Measures;
use Rekalogika\Analytics\Contracts\Result;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class DefaultAnalyticsChartBuilder implements AnalyticsChartBuilder
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private ChartBuilderInterface $chartBuilder,
        private Stringifier $stringifier,
        private ChartConfiguration $configuration,
    ) {}

    #[\Override]
    public function createChart(
        Result $result,
        ChartType $chartType = ChartType::Auto,
    ): Chart {
        if ($chartType === ChartType::Auto) {
            return $this->createAutoChart($result);
        }

        if ($chartType === ChartType::Bar) {
            return $this->createBarChart($result);
        }

        if ($chartType === ChartType::StackedBar) {
            return $this->createGroupedBarChart($result, true);
        }

        if ($chartType === ChartType::GroupedBar) {
            return $this->createGroupedBarChart($result, false);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function createAutoChart(Result $result): Chart
    {
        $measures = $result->getTable()->first()?->getMeasures();

        if ($measures === null) {
            throw new UnsupportedData('Measures not found');
        }

        $tuple = $result->getTable()->first()?->getTuple();

        if ($tuple === null) {
            throw new UnsupportedData('No data found');
        }

        if (\count($tuple) === 1) {
            return $this->createBarChart($result);
        } elseif (\count($tuple) === 2) {
            return $this->createGroupedBarChart($result, false);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    private function createBarChart(Result $result): Chart
    {
        $colorDispenser = $this->createColorDispenser();
        $measures = $result->getTable()->first()?->getMeasures();

        if ($measures === null) {
            throw new UnsupportedData('Measures not found');
        }

        $selectedMeasures = $this->selectMeasures($measures);
        $numMeasures = \count($selectedMeasures);

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;

        // populate labels

        foreach ($selectedMeasures as $key) {
            $measure = $measures->get($key);

            $dataSets[$key]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$key]['data'] = [];

            $color = $colorDispenser->dispenseColor();
            $dataSets[$key]['backgroundColor'] = $color . $this->configuration->areaTransparency;
            $dataSets[$key]['borderColor'] = $color;
            $dataSets[$key]['borderWidth'] = $this->configuration->areaBorderWidth;

            if ($yTitle === null) {
                $unit = $measure->getUnit();

                if ($unit === null) {
                    if ($numMeasures === 1) {
                        $yTitle = $this->stringifier->toString($measure->getLabel());
                    }
                } else {
                    if ($numMeasures === 1) {
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
        }

        // populate data

        foreach ($result->getTable() as $row) {
            $dimensions = $row->getTuple();

            if (\count($dimensions) !== 1) {
                throw new UnsupportedData('Expected only one member');
            }

            $dimension = $dimensions->first();

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

            foreach ($selectedMeasures as $key) {
                $measure = $measures->get($key);

                $dataSets[$key]['data'][] = $measure->getNumericValue();
            }
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

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
        ]);

        return $chart;
    }

    private function createGroupedBarChart(Result $result, bool $stacked): Chart
    {
        $colorDispenser = $this->createColorDispenser();
        $measure = $result->getTable()->first()?->getMeasures()->first();

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
            /** @psalm-suppress MixedAssignment */
            $secondDimensions[] = $row->getTuple()->getByIndex(1)->getMember();
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

                $dataSets[$signature]['data'][] = $measure->getNumericValue();

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

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

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
            ],
            'scales' => [
                'x' => [
                    'title' => $xTitle,
                    'stacked' => $stacked,
                ],
                'y' => [
                    'title' => $yTitle,
                    'stacked' => $stacked,
                ],
            ],
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
                return [$measure->getKey()];
            }

            if ($selectedUnit === null) {
                $selectedUnit = $unit;
            }

            if (
                $selectedUnit !== null &&
                $selectedUnit->getSignature() === $unit?->getSignature()
            ) {
                $selectedMeasures[] = $measure->getKey();
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
