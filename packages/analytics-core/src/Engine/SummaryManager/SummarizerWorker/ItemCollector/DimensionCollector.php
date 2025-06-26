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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector;

use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultNormalRow;

final class DimensionCollector
{
    /**
     * @var array<string,DimensionByNameCollector>
     */
    private array $collectors = [];

    /**
     * @var array<string,DefaultMeasure>
     */
    private array $measures = [];

    public function getResult(): Items
    {
        $uniqueDimensionsByKey = [];

        foreach ($this->collectors as $name => $collector) {
            $uniqueDimensionsByKey[$name] = $collector->getResult();
        }

        return new Items(
            dimensions: $uniqueDimensionsByKey,
            measures: $this->measures,
        );
    }

    private function getCollectorForName(string $name): DimensionByNameCollector
    {
        return $this->collectors[$name] ??= new DimensionByNameCollector($name);
    }

    public function processDimensions(DefaultNormalRow $normalRow): void
    {
        foreach ($normalRow as $dimension) {
            $this->getCollectorForName($dimension->getName())
                ->addDimension($dimension);
        }
    }

    public function processMeasure(DefaultMeasure $measure): void
    {
        $this->measures[$measure->getName()]
            ??= DefaultMeasure::createNullFromSelf($measure);
    }
}
