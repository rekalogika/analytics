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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Core\Exception\UnexpectedValueException;
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

    private ?string $lastEarlierDimensionsInDimensionsSignature = null;

    private ?DefaultDimension $previousDimension = null;

    public function __construct(
        private readonly string $name,
        private readonly bool $hasTieredOrder,
    ) {}

    public function getResult(): DimensionCollection
    {
        $firstDimension = $this->dimensions[array_key_first($this->dimensions) ?? throw new UnexpectedValueException('No dimensions found in the collector.')];

        $dimensions = array_values($this->dimensions);

        if ($dimensions === []) {
            return new DimensionCollection(
                name: $this->name,
                dimensions: [],
            );
        }

        if ($this->hasTieredOrder && $firstDimension->isSequence()) {
            $dimensions = $this->fillGaps($dimensions);
        }

        return new DimensionCollection(
            name: $this->name,
            dimensions: $dimensions,
        );
    }

    /**
     * @param list<DefaultDimension> $earlierDimensionsInDimensions
     */
    public function addDimension(
        array $earlierDimensionsInDimensions,
        DefaultDimension $dimension,
    ): void {
        $previousDimension = $this->previousDimension;
        $this->previousDimension = $dimension;

        // ensure the dimensions has the correct key

        if ($dimension->getName() !== $this->name) {
            throw new UnexpectedValueException(\sprintf(
                'Dimension key "%s" does not match the collector key "%s"',
                $dimension->getName(),
                $this->name,
            ));
        }

        // if already exists, then skip

        $signature = DimensionUtil::getDimensionSignature($dimension);

        if (isset($this->dimensions[$signature])) {
            return;
        }

        // if previousDimension is null, check if earlierdimension in dimensions
        // has changed. if changed, then regard previousDimension as null

        if ($previousDimension === null) {
            $lastEarlierDimensionsInDimensionsSignature = DimensionUtil::getDimensionsSignature(
                $earlierDimensionsInDimensions,
            );

            if (
                $this->lastEarlierDimensionsInDimensionsSignature !== $lastEarlierDimensionsInDimensionsSignature
            ) {
                $previousDimension = null;
            }

            $this->lastEarlierDimensionsInDimensionsSignature = $lastEarlierDimensionsInDimensionsSignature;
        }

        // if previousDimension is null, then it is a candidate for the first
        // dimension in the array.

        if ($previousDimension === null) {
            $this->insertDimensionAtTheBeginning($dimension);

            return;
        }

        // else insert the dimension after the previousDimension

        $this->insertDimensionAfter($dimension, $previousDimension);
    }

    /**
     * @return list<Dimension>
     */
    public function getDimensions(): array
    {
        return array_values($this->dimensions);
    }

    /**
     * Adds $dimension after $previousDimension in the $this->dimensions array
     */
    private function insertDimensionAfter(
        DefaultDimension $dimension,
        DefaultDimension $previousDimension,
    ): void {
        $newDimensions = [];

        foreach ($this->dimensions as $key => $value) {
            $newDimensions[$key] = $value;

            if (DimensionUtil::isDimensionSame($value, $previousDimension)) {
                $signature = DimensionUtil::getDimensionSignature($dimension);
                $newDimensions[$signature] = $dimension;
            }
        }

        $this->dimensions = $newDimensions;
    }

    private function insertDimensionAtTheBeginning(
        DefaultDimension $dimension,
    ): void {
        $signature = DimensionUtil::getDimensionSignature($dimension);

        $this->dimensions = [
            $signature => $dimension,
        ] + $this->dimensions;
    }

    // private function insertDimensionAtTheEnd(
    //     Dimension $dimension,
    // ): void {
    //     $signature = DimensionUtil::getDimensionSignature($dimension);

    //     $this->dimensions[$signature] = $dimension;
    // }

    /**
     * @param non-empty-list<DefaultDimension> $dimensions
     * @return non-empty-list<DefaultDimension>
     */
    private function fillGaps(array $dimensions): array
    {
        return GapFiller::process($dimensions);
    }
}
