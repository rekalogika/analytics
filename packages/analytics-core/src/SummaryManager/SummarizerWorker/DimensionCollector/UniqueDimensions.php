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

/**
 * @implements \IteratorAggregate<UniqueDimensionsByKey>
 */
final readonly class UniqueDimensions implements \IteratorAggregate, \Countable
{
    /**
     * @param array<string,UniqueDimensionsByKey> $dimensions
     */
    public function __construct(
        private array $dimensions,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    public function get(string $key): UniqueDimensionsByKey
    {
        return $this->dimensions[$key]
            ?? throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $key));
    }


    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }
}
