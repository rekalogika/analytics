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

use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\Util\DimensionUtil;

/**
 * Get unique dimensions while preserving the order of the dimensions.
 */
final class DimensionByNameCollector
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    public function __construct(
        private readonly string $name,
    ) {}

    public function getResult(): DimensionCollection
    {
        $dimensions = $this->dimensions;

        if ($dimensions === []) {
            return new DimensionCollection(
                name: $this->name,
                dimensions: [],
            );
        }

        $dimensions = DimensionUtil::sortDimensions(array_values($dimensions));

        // if ($firstDimension->isSequence()) {
        //     $dimensions = $this->fillGaps($dimensions);
        // }

        return new DimensionCollection(
            name: $this->name,
            dimensions: $dimensions,
        );
    }

    public function addDimension(DefaultDimension $dimension): void
    {
        $signature = DimensionUtil::getDimensionSignature($dimension);

        if (isset($this->dimensions[$signature])) {
            return;
        }

        $this->dimensions[$signature] = $dimension;
    }

    // /**
    //  * @param non-empty-list<DefaultDimension> $dimensions
    //  * @return non-empty-list<DefaultDimension>
    //  */
    // private function fillGaps(array $dimensions): array
    // {
    //     return GapFiller::process($dimensions);
    // }
}
