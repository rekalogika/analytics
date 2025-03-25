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
use Rekalogika\Analytics\Contracts\Tuple;
use Rekalogika\Analytics\Util\DimensionUtil;

/**
 * @implements \IteratorAggregate<string,Dimension>
 */
final readonly class DefaultTuple implements Tuple, \IteratorAggregate
{
    public function __construct(
        private Dimensions $dimensions,
    ) {}

    #[\Override]
    public function getMembers(): array
    {
        $members = [];

        foreach ($this->dimensions as $dimension) {
            /** @psalm-suppress MixedAssignment */
            $members[$dimension->getKey()] = $dimension->getMember();
        }

        return $members;
    }

    #[\Override]
    public function get(string $key): Dimension
    {
        return $this->dimensions->get($key);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return $this->dimensions->has($key);
    }

    #[\Override]
    public function count(): int
    {
        return $this->dimensions->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    #[\Override]
    public function first(): ?Dimension
    {
        return $this->dimensions->first();
    }

    #[\Override]
    public function getByIndex(int $index): Dimension
    {
        return $this->dimensions->getByIndex($index);
    }

    #[\Override]
    public function isSame(Tuple $other): bool
    {
        foreach ($this->dimensions as $key => $dimension) {
            if (!$other->has($key)) {
                return false;
            }

            $otherDimension = $other->get($key);

            if (!DimensionUtil::isDimensionSame($dimension, $otherDimension)) {
                return false;
            }
        }

        return true;
    }
}
