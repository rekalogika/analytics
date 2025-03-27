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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector;

use Rekalogika\Analytics\Contracts\Dimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultTuple;

final class DimensionCollector
{
    /**
     * @var array<string,DimensionByKeyCollector>
     */
    private array $collectors = [];

    public function getResult(): UniqueDimensions
    {
        $result = [];

        foreach ($this->collectors as $key => $collector) {
            $result[$key] = $collector->getResult();
        }

        return new UniqueDimensions($result);
    }

    private function getCollectorForKey(string $key): DimensionByKeyCollector
    {
        return $this->collectors[$key] ??= new DimensionByKeyCollector($key);
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

    /**
     * @return list<Dimension>
     */
    public function getDimensionsByKey(string $key): array
    {
        return $this->getCollectorForKey($key)->getDimensions();
    }
}
