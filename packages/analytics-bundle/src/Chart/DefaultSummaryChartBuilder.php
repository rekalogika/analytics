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

use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class DefaultSummaryChartBuilder implements SummaryChartBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private ChartBuilderInterface $chartBuilder,
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
            $labels[] = $this->stringify($member);

            $measures = $row->getMeasures();
            $measure = array_shift($measures);

            if ($measure === null) {
                throw new \InvalidArgumentException('Measure not found');
            }

            $label = $this->stringify($measure->getLabel());

            $data[] = $measure->getRawValue();
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
            'locale' => $this->translator->getLocale(),
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

    private function stringify(mixed $input): string
    {
        if ($input instanceof TranslatableMessage) {
            return $input->trans($this->translator);
        }

        if ($input instanceof \Stringable) {
            return (string) $input;
        }

        if (\is_string($input)) {
            return $input;
        }

        if (\is_int($input)) {
            return (string) $input;
        }

        if (\is_float($input)) {
            return (string) $input;
        }

        if ($input instanceof \BackedEnum) {
            return (string) $input->value;
        }

        if ($input instanceof \UnitEnum) {
            return $input->name;
        }

        if (\is_bool($input)) {
            return $input ?
                (new TranslatableMessage('True'))->trans($this->translator) : (new TranslatableMessage('False'))->trans($this->translator);
        }

        if (\is_object($input)) {
            return \sprintf('%s:%s', $input::class, spl_object_id($input));
        }

        if ($input === null) {
            return '-';
        }

        return get_debug_type($input);
    }
}
