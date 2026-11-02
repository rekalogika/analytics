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

namespace Rekalogika\Analytics\Frontend\Chart;

use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Frontend\Exception\AnalyticsFrontendException;
use Symfony\UX\Chartjs\Model\Chart;

interface ChartGenerator
{
    /**
     * @param list<string> $dimensions
     * @param list<string> $measures
     * @throws UnsupportedData If the dimensions or measures are not suitable
     * for the chart type
     * @throws AnalyticsFrontendException
     */
    public function createChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart;
}
