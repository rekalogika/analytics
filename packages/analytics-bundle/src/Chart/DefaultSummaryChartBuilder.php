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

use Colors\RandomColor;
use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\Query\Measures;
use Rekalogika\Analytics\Query\Result;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class DefaultSummaryChartBuilder implements SummaryChartBuilder
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private ChartBuilderInterface $chartBuilder,
        private Stringifier $stringifier,
    ) {}

    #[\Override]
    public function createChart(
        Result $result,
    ): Chart {
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
            $dataSets[$key]['backgroundColor'] = $this->dispenseColor();

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

            if ($selectedUnit === $unit) {
                $selectedMeasures[] = $measure->getKey();
            }
        }

        return $selectedMeasures;
    }

    private function dispenseColor(): string
    {
        $color = RandomColor::one([
            'alpha' => 0.5,
        ]);

        if (!\is_string($color)) {
            throw new \LogicException('Failed to generate color');
        }

        return $color;
    }
}
