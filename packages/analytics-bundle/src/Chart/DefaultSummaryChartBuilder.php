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
        $measures = $result->getTable()->getFirstRow()?->getMeasures();

        if ($measures === null) {
            throw new UnsupportedData('Measures not found');
        }

        $selectedMeasures = $this->selectMeasures($measures);

        $labels = [];
        $dataSets = [];
        $label = null;

        // populate labels

        foreach ($selectedMeasures as $key) {
            $measure = $measures->get($key);

            $dataSets[$key]['label'] = $this->stringifier->toString($measure->getLabel());
            $dataSets[$key]['data'] = [];
            $dataSets[$key]['backgroundColor'] = $this->dispenseColor();
        }

        // populate data

        foreach ($result->getTable() as $row) {
            $members = $row->getTuple()->getMembers();

            if (\count($members) !== 1) {
                throw new UnsupportedData('Expected only one member');
            }

            /** @psalm-suppress MixedAssignment */
            $member = array_shift($members);
            $labels[] = $this->stringifier->toString($member);

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

        $chart->setOptions([
            'responsive' => true,
            'locale' => $this->localeSwitcher->getLocale(),
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'title' => [
                    'display' => false,
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
            } else {
                break;
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
