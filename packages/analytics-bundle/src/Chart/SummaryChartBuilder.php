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
use Symfony\UX\Chartjs\Model\Chart;

interface SummaryChartBuilder
{
    public function createChart(Result $result): Chart;
}
