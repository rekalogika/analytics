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

namespace Rekalogika\Analytics\Bundle\Chart;

use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Query\Measures;
use Rekalogika\Analytics\Query\Result;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class DefaultSummaryChartBuilder implements SummaryChartBuilder
{
    private ColorDispenser $colorDispenser;

    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private ChartBuilderInterface $chartBuilder,
        private Stringifier $stringifier,
    ) {
        $this->colorDispenser = new ColorDispenser();
    }

    #[\Override]
    public function createChart(Result $result): Chart
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
            return $this->createStackedBarChart($result);
        }

        throw new UnsupportedData('Unsupported chart type');
    }

    public function createBarChart(Result $result): Chart
    {
        $measures = $result->getTable()->first()?->getMeasures();

        if ($measures === null) {
            throw new UnsupportedData('Measures not found');
        }

        $selectedMeasures = $this->selectMeasures($measures);

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;

        // populate labels

        foreach ($selectedMeasures as $key) {
            $measure = $measures->get($key);

            $dataSets[$key]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$key]['data'] = [];

            $color = $this->dispenseColor();
            $dataSets[$key]['backgroundColor'] = $color . 'a0';
            $dataSets[$key]['borderColor'] = $color;

            if ($yTitle !== null) {
                continue;
            }

            $unit = $measure->getUnit();

            if ($unit !== null) {
                $yTitle = $this->stringifier->toString($unit);
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

            $labels[] = $this->stringifier->toString($dimension->getMember());

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

    public function createStackedBarChart(Result $result): Chart
    {
        $measure = $result->getTable()->first()?->getMeasures()->first();

        if ($measure === null) {
            throw new UnsupportedData('Measures not found');
        }

        $labels = [];
        $dataSets = [];

        $xTitle = null;
        $yTitle = null;

        // collect second dimension

        $secondDimensions = [];

        foreach ($result->getTable() as $row) {
            /** @psalm-suppress MixedAssignment */
            $secondDimensions[] = $row->getTuple()->getByIndex(1)->getRawMember();
        }

        $secondDimensions = array_unique($secondDimensions, SORT_REGULAR);

        // populate data

        foreach ($result->getTree() as $node) {
            $labels[] = $this->stringifier->toString($node->getRawMember());

            if ($xTitle === null) {
                $xTitle = $this->stringifier->toString($node->getLabel());
            }

            /** @psalm-suppress MixedAssignment */
            foreach ($secondDimensions as $dimension2) {
                $node2 = $node->traverse($dimension2);
                $signature = $this->getSignature($dimension2);

                if (!isset($dataSets[$signature]['backgroundColor'])) {
                    $color = $this->dispenseColor();
                    $dataSets[$signature]['backgroundColor'] = $color . 'a0';
                    $dataSets[$signature]['borderColor'] = $color;
                }

                if ($node2 === null) {
                    $dataSets[$signature]['data'][] = 0;

                    continue;
                }

                if (!isset($dataSets[$signature]['label'])) {
                    $dataSets[$signature]['label'] = $this->stringifier->toString($node2->getMember());
                }

                $children = iterator_to_array($node2, false);
                $valueNode = $children[0];

                $dataSets[$signature]['data'][] = $valueNode->getNumericValue();

                if ($yTitle === null) {
                    $unit = $valueNode->getUnit();

                    if ($unit !== null) {
                        $yTitle = \sprintf(
                            '%s - %s',
                            $this->stringifier->toString($valueNode->getMember()) ?? '-',
                            $this->stringifier->toString($unit) ?? '-',
                        );
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
                'legend' => $legend,
                'title' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'title' => $xTitle,
                    'stacked' => true,
                ],
                'y' => [
                    'title' => $yTitle,
                    'stacked' => true,
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

    private function dispenseColor(): string
    {
        return $this->colorDispenser->dispenseColor();
    }

    private function getSignature(mixed $variable): string
    {
        if (\is_object($variable)) {
            return (string) spl_object_id($variable);
        }

        return hash('xxh128', serialize($variable));
    }
}
