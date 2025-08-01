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

use Rekalogika\PivotTable\Table\Cell;
use Rekalogika\PivotTable\Table\ElementContext;
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
        private DefaultContext $context,
    ) {
        $this->cells = $this->mergeCells($cells);
    }

    #[\Override]
    public function getContext(): ElementContext
    {
        return $this->context;
    }

    /**
     * @param list<DefaultCell> $cells
     * @return list<DefaultCell>
     */
    private function mergeCells(array $cells): array
    {
        $mergedCells = [];
        $lastCell = null;

        foreach ($cells as $cell) {
            if (
                (
                    $lastCell instanceof DefaultFooterCell
                    || $lastCell instanceof DefaultFooterHeaderCell
                )
                && (
                    $cell instanceof DefaultFooterCell
                    || $cell instanceof DefaultFooterHeaderCell
                )
                && $lastCell->getContent() === $cell->getContent()
                && $lastCell->getRowSpan() === $cell->getRowSpan()
            ) {
                $lastCell = $lastCell
                    ->withColumnSpan($lastCell->getColumnSpan() + $cell->getColumnSpan());
                array_pop($mergedCells);
                $mergedCells[] = $lastCell;

                continue;
            }

            if (
                $lastCell instanceof DefaultFooterHeaderCell
                && (
                    $cell instanceof DefaultFooterHeaderCell
                    || $cell instanceof DefaultFooterCell
                )
                && $cell->getContent() === ''
                && $lastCell->getRowSpan() === $cell->getRowSpan()
            ) {
                $lastCell = $lastCell
                    ->withColumnSpan($lastCell->getColumnSpan() + $cell->getColumnSpan());
                array_pop($mergedCells);
                $mergedCells[] = $lastCell;

                continue;
            }

            $mergedCells[] = $cell;
            $lastCell = $cell;
        }

        return $mergedCells;
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
        return new self([...$this->cells, $cell], $this->context);
    }

    public function appendRow(DefaultRow $row): static
    {
        return new self([...$this->cells, ...$row->cells], $this->context);
    }
}
