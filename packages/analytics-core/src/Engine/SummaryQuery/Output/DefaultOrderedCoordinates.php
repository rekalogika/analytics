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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\OrderedCoordinates;
use Rekalogika\Contracts\Rekapager\Exception\OutOfBoundsException;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final class DefaultOrderedCoordinates implements OrderedCoordinates, \IteratorAggregate
{
    /**
     * @param list<string> $order
     */
    public function __construct(
        private readonly DefaultCoordinates $coordinates,
        private readonly array $order,
    ) {
        $sortedOrder = $order;
        sort($sortedOrder);

        if ($coordinates->getDimensionality() !== $sortedOrder) {
            throw new InvalidArgumentException(
                'The order of dimensions does not match the dimensionality of the coordinates.',
            );
        }
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->coordinates->getSummaryClass();
    }

    #[\Override]
    public function getPredicate(): ?Expression
    {
        return $this->coordinates->getPredicate();
    }

    #[\Override]
    public function getDimensionality(): array
    {
        return $this->order;
    }

    #[\Override]
    public function get(mixed $key): mixed
    {
        return $this->coordinates->get($key);
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $key = $this->order[$index]
            ?? throw new OutOfBoundsException("Index {$index} is out of bounds for the ordered coordinates.");

        return $this->coordinates->get($key);
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        return $this->coordinates->has($key);
    }

    #[\Override]
    public function first(): mixed
    {
        $key = $this->order[0] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->coordinates->get($key);
    }

    #[\Override]
    public function last(): mixed
    {
        $lastIndex = \count($this->order) - 1;
        $key = $this->order[$lastIndex] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->coordinates->get($key);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->order);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->order as $key) {
            $dimension = $this->coordinates->get($key);

            if ($dimension === null) {
                throw new InvalidArgumentException(
                    "Dimension with key '{$key}' does not exist in the coordinates.",
                );
            }

            yield $key => $dimension;
        }
    }

    public function getMeasureName(): ?string
    {
        return $this->coordinates->getMeasureName();
    }
}
