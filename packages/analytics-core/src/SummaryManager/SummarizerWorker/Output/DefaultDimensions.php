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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Dimension;
use Rekalogika\Analytics\Contracts\Dimensions;

/**
 * @implements \IteratorAggregate<string,Dimension>
 */
final readonly class DefaultDimensions implements Dimensions, \IteratorAggregate
{
    /**
     * @var array<string,Dimension>
     */
    private array $dimensions;

    /**
     * @param iterable<Dimension> $dimensions
     */
    public function __construct(
        iterable $dimensions,
    ) {
        $dimensionsArray = [];

        foreach ($dimensions as $dimension) {
            $dimensionsArray[$dimension->getKey()] = $dimension;
        }

        $this->dimensions = $dimensionsArray;
    }

    #[\Override]
    public function first(): ?Dimension
    {
        $firstKey = array_key_first($this->dimensions);

        if ($firstKey === null) {
            return null;
        }

        return $this->dimensions[$firstKey] ?? null;
    }

    #[\Override]
    public function get(string $key): Dimension
    {
        return $this->dimensions[$key]
            ?? throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $key));
    }

    #[\Override]
    public function getByIndex(int $index): Dimension
    {
        $keys = array_keys($this->dimensions);

        if (!isset($keys[$index])) {
            throw new \InvalidArgumentException(\sprintf('Dimension at index "%d" not found', $index));
        }

        return $this->dimensions[$keys[$index]];
    }

    #[\Override]
    public function has(string $key): bool
    {
        return isset($this->dimensions[$key]);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }
}
