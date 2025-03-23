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

namespace Rekalogika\PivotTable\Table;

/**
 * @implements \IteratorAggregate<Cell>
 */
final readonly class Row implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Cell> $cells
     */
    public function __construct(
        private array $cells = [],
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->cells);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->cells);
    }

    public function getWidth(): int
    {
        return array_reduce(
            $this->cells,
            fn(int $carry, Cell $cell): int => $carry + $cell->getColumnSpan(),
            0,
        );
    }

    public function appendCell(Cell $cell): static
    {
        return new self([...$this->cells, $cell]);
    }

    public function appendRow(Row $row): static
    {
        return new self([...$this->cells, ...$row->cells]);
    }
}
