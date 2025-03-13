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

final readonly class ChartConfiguration
{
    public function __construct(
        /**
         * Will be appended to HTML color. e.g. if the value is 'a0', and the
         * base color is '#ff0000', the final color will be '#ff0000a0'
         */
        public string $areaTransparency = '60',

        /**
         * The border of the area in the chart.
         */
        public int $areaBorderWidth = 1,

        /**
         * @var array<string,string|int>
         */
        public array $labelFont = [
            'size' => 14,
            'weight' => 'bold',
        ],
    ) {}
}
