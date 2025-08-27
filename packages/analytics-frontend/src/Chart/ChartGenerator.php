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
     * @param non-empty-list<string> $dimensions
     * @param non-empty-list<string> $measures
     * @throws UnsupportedData
     * @throws AnalyticsFrontendException
     */
    public function createChart(
        CubeCell $cube,
        array $dimensions,
        array $measures,
        ChartType $chartType = ChartType::Auto,
    ): Chart;
}
