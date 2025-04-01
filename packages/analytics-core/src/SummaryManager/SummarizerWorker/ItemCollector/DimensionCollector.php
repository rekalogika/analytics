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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector;

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class DimensionCollector
{
    /**
     * @var array<string,DimensionByKeyCollector>
     */
    private array $collectors = [];

    /**
     * @var array<string,DefaultMeasure>
     */
    private array $measures = [];

    public function __construct(
        private readonly bool $hasTieredOrder,
    ) {}

    public function getResult(): Items
    {
        $uniqueDimensionsByKey = [];

        foreach ($this->collectors as $key => $collector) {
            $uniqueDimensionsByKey[$key] = $collector->getResult();
        }

        return new Items(
            dimensions: $uniqueDimensionsByKey,
            measures: $this->measures,
        );
    }

    private function getCollectorForKey(string $key): DimensionByKeyCollector
    {
        return $this->collectors[$key] ??= new DimensionByKeyCollector(
            key: $key,
            hasTieredOrder: $this->hasTieredOrder,
        );
    }

    public function processTuple(DefaultTuple $tuple): void
    {
        $earlierDimensions = [];

        foreach ($tuple as $dimension) {
            $key = $dimension->getKey();

            $this->getCollectorForKey($key)->addDimension(
                earlierDimensionsInTuple: $earlierDimensions,
                dimension: $dimension,
            );

            $earlierDimensions[] = $dimension;
        }
    }

    public function processMeasure(DefaultMeasure $measure): void
    {
        $this->measures[$measure->getKey()]
            ??= DefaultMeasure::createNullFromSelf($measure);
    }
}
