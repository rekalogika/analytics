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

namespace Rekalogika\Analytics\Frontend\Chart\Configuration;

final readonly class ChartConfiguration
{
    private ColorDispenser $colorDispenser;

    public function __construct(
        /**
         * Will be appended to HTML color. e.g. if the value is 'a0', and the
         * base color is '#ff0000', the final color will be '#ff0000a0'
         */
        private string $areaTransparency = '60',

        /**
         * The border of the area in the chart.
         */
        private int $areaBorderWidth = 1,
        private string $labelFontSize = '14',
        private string $labelFontWeight = 'bold',
    ) {
        $this->colorDispenser = new ColorDispenser();
    }

    public function createChartElementConfiguration(): ChartArea
    {
        $baseColor = $this->colorDispenser->dispenseColor();

        return new ChartArea(
            baseColor: $baseColor,
            areaTransparency: $this->areaTransparency,
            borderWidth: $this->areaBorderWidth,
        );
    }

    public function getChartLabelFont(): ChartLabelFont
    {
        return new ChartLabelFont(
            size: $this->labelFontSize,
            weight: $this->labelFontWeight,
        );
    }
}
