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

use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Frontend\Exception\AnalyticsFrontendException;
use Symfony\UX\Chartjs\Model\Chart;

interface ChartGenerator
{
    /**
     * @throws UnsupportedData
     * @throws AnalyticsFrontendException
     */
    public function createChart(
        Result $result,
        ChartType $chartType = ChartType::Auto,
    ): Chart;
}
