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

use Rekalogika\Analytics\Contracts\Result\Dimensions;
use Rekalogika\Analytics\Exception\InvalidArgumentException;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final readonly class DefaultDimensions implements Dimensions, \IteratorAggregate
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions;

    /**
     * @param iterable<DefaultDimension> $dimensions
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
    public function first(): ?DefaultDimension
    {
        $firstKey = array_key_first($this->dimensions);

        if ($firstKey === null) {
            return null;
        }

        return $this->dimensions[$firstKey] ?? null;
    }

    #[\Override]
    public function get(string $key): DefaultDimension
    {
        return $this->dimensions[$key]
            ?? throw new InvalidArgumentException(\sprintf(
                'Dimension "%s" not found',
                $key,
            ));
    }

    #[\Override]
    public function getByIndex(int $index): DefaultDimension
    {
        $keys = array_keys($this->dimensions);

        if (!isset($keys[$index])) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension at index "%d" not found',
                $index,
            ));
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

    public function isSame(Dimensions $dimensions): bool
    {
        if ($this->count() !== $dimensions->count()) {
            return false;
        }

        foreach ($this->dimensions as $key => $dimension) {
            if (!$dimensions->has($key)) {
                return false;
            }

            if (!$dimension->isSame($dimensions->get($key))) {
                return false;
            }
        }

        return true;
    }
}
