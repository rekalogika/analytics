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
        $labels = [];
        $data = [];
        $label = null;

        foreach ($result->getTable() as $row) {
            $members = $row->getTuple()->getMembers();

            if (\count($members) !== 1) {
                throw new \InvalidArgumentException('Expected only one member');
            }

            /** @psalm-suppress MixedAssignment */
            $member = array_shift($members);
            $labels[] = $this->stringifier->toString($member);

            $measures = $row->getMeasures();
            $measure = array_shift($measures);

            if ($measure === null) {
                throw new \InvalidArgumentException('Measure not found');
            }

            $label = $this->stringifier->toString($measure->getLabel());

            $data[] = $measure->getNumericValue();
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'data' => $data,
                ],
            ],
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
                    'display' => true,
                    'text' => $label,
                ],
            ],
        ]);

        return $chart;
    }
}
