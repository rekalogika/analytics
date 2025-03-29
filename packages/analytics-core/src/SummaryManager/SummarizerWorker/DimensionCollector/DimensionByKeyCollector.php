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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Util\DimensionUtil;

/**
 * Get unique dimensions while preserving the order of the dimensions.
 */
final class DimensionByKeyCollector
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions = [];

    private ?string $lastEarlierDimensionsInTupleSignature = null;

    private ?DefaultDimension $previousDimension = null;

    public function __construct(
        private readonly string $key,
    ) {}

    public function getResult(): UniqueDimensionsByKey
    {
        return new UniqueDimensionsByKey(
            key: $this->key,
            dimensions: array_values($this->dimensions),
        );
    }

    /**
     * @param list<DefaultDimension> $earlierDimensionsInTuple
     */
    public function addDimension(
        array $earlierDimensionsInTuple,
        DefaultDimension $dimension,
    ): void {
        $previousDimension = $this->previousDimension;
        $this->previousDimension = $dimension;

        // ensure the dimensions has the correct key

        if ($dimension->getKey() !== $this->key) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Dimension key "%s" does not match the collector key "%s"',
                    $dimension->getKey(),
                    $this->key,
                ),
            );
        }

        // if already exists, then skip

        $signature = DimensionUtil::getDimensionSignature($dimension);

        if (isset($this->dimensions[$signature])) {
            return;
        }

        // if previousDimension is null, check if earlierdimension in tuple has
        // changed. if changed, then regard previousDimension as null

        if ($previousDimension === null) {
            $lastEarlierDimensionsInTupleSignature = DimensionUtil::getDimensionsSignature(
                $earlierDimensionsInTuple,
            );

            if (
                $this->lastEarlierDimensionsInTupleSignature !== $lastEarlierDimensionsInTupleSignature
            ) {
                $previousDimension = null;
            }

            $this->lastEarlierDimensionsInTupleSignature = $lastEarlierDimensionsInTupleSignature;
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
}
