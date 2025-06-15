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

use Rekalogika\Analytics\Core\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;

/**
 * @implements \IteratorAggregate<DimensionCollection>
 */
final readonly class Items implements \IteratorAggregate, \Countable
{
    /**
     * @param array<string,DimensionCollection> $dimensions
     * @param array<string,DefaultMeasure> $measures
     */
    public function __construct(
        private array $dimensions,
        private array $measures,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    public function getDimensions(string $name): DimensionCollection
    {
        return $this->dimensions[$name]
            ?? throw new InvalidArgumentException(\sprintf(
                'Dimension "%s" not found',
                $name,
            ));
    }

    public function getMeasure(string $name): DefaultMeasure
    {
        return $this->measures[$name]
            ?? throw new InvalidArgumentException(\sprintf(
                'Measure "%s" not found',
                $name,
            ));
    }

    public function getKeyAfter(?string $name): ?string
    {
        $names = array_keys($this->dimensions);

        if ($name === null) {
            return $names[0] ?? null;
        }

        $nameIndex = array_search($name, $names, true);

        if ($nameIndex === false) {
            return null;
        }

        $nextKeyIndex = $nameIndex + 1;

        if (!isset($names[$nextKeyIndex])) {
            return null;
        }

        return $names[$nextKeyIndex];
    }


    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }
}
