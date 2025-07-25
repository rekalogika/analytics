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

namespace Rekalogika\PivotTable\Implementation\Table;

use Rekalogika\PivotTable\Block\Block;
use Rekalogika\PivotTable\Table\Cell;
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\TableVisitor;

/**
 * @implements \IteratorAggregate<Cell>
 */
final readonly class DefaultRow implements \IteratorAggregate, Row
{
    /**
     * @var list<DefaultCell> $cells
     */
    private array $cells;

    /**
     * @param list<DefaultCell> $cells
     */
    public function __construct(
        array $cells,
        private ?Block $generatingBlock,
    ) {
        $newCells = [];

        // merge cells with the same content
        $lastCell = null;

        foreach ($cells as $cell) {
            if (
                $lastCell instanceof DefaultCell
                && $lastCell->getContent() !== null
                && $lastCell->getContent() === $cell->getContent()
                && $lastCell->getRowSpan() === $cell->getRowSpan()
            ) {
                $lastCell = $lastCell
                    ->withColumnSpan($lastCell->getColumnSpan() + $cell->getColumnSpan());
                array_pop($newCells); // remove last cell
                $newCells[] = $lastCell; // add last cell or current

                continue;
            }

            $newCells[] = $cell;

            $lastCell = $cell;
        }

        $this->cells = $newCells;
    }

    public function getGeneratingBlock(): ?Block
    {
        return $this->generatingBlock;
    }

    #[\Override]
    public function accept(TableVisitor $visitor): mixed
    {
        return $visitor->visitRow($this);
    }

    #[\Override]
    public function getTagName(): string
    {
        return 'tr';
    }

    /**
     * @return \Traversable<DefaultCell>
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->cells);
    }

    public function getFirstCell(): DefaultCell
    {
        if ($this->cells === []) {
            throw new \LogicException('Row has no cells.');
        }

        return $this->cells[0];
    }

    public function getLastCell(): DefaultCell
    {
        if ($this->cells === []) {
            throw new \LogicException('Row has no cells.');
        }

        return $this->cells[\count($this->cells) - 1];
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
            fn(int $carry, DefaultCell $cell): int => $carry + $cell->getColumnSpan(),
            0,
        );
    }

    public function appendCell(DefaultCell $cell): static
    {
        return new self([...$this->cells, $cell], $this->generatingBlock);
    }

    public function appendRow(DefaultRow $row): static
    {
        return new self([...$this->cells, ...$row->cells], $this->generatingBlock);
    }
}
