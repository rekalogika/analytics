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

use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\RowGroup;

/**
 * @implements \IteratorAggregate<Row>
 */
final class DefaultRows implements \IteratorAggregate, RowGroup
{
    /**
     * @var int<0,max>|null
     */
    private ?int $width = null;

    /**
     * @param list<DefaultRow> $rows
     */
    public function __construct(
        private readonly array $rows,
        private readonly DefaultContext $context,
    ) {}

    public static function createFromCell(
        DefaultCell $cell,
        DefaultContext $context,
    ): self {
        return new self([new DefaultRow([$cell], $context)], $context);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->rows);
    }

    /**
     * @return \Traversable<DefaultRow>
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rows);
    }

    /**
     * @return list<DefaultRow>
     */
    public function toArray(): array
    {
        return $this->rows;
    }

    /**
     * @return int<0,max>
     */
    public function getWidth(): int
    {
        if ($this->width !== null) {
            return $this->width;
        }

        $maxWidth = 0;

        foreach ($this->rows as $row) {
            $maxWidth = max($maxWidth, $row->getWidth());
        }

        return $this->width = $maxWidth;
    }

    public function getHeight(): int
    {
        return \count($this->rows);
    }

    public function getFirstRow(): DefaultRow
    {
        return $this->rows[0] ?? new DefaultRow([], $this->context);
    }

    public function getSecondToLastRows(): DefaultRows
    {
        return new self(\array_slice($this->rows, 1), $this->context);
    }

    public function appendRow(DefaultRow $row): DefaultRows
    {
        return new self([...$this->rows, $row], $this->context);
    }

    public function appendBelow(DefaultRows $rows): DefaultRows
    {
        return new self([...$this->rows, ...$rows->toArray()], $this->context);
    }

    public function appendRight(DefaultRows $rows): DefaultRows
    {
        $height = max($this->getHeight(), $rows->getHeight());
        $rowsToAdd = $rows->toArray();

        $newRows = [];

        for ($i = 0; $i < $height; $i++) {
            $newRows[$i] = $this->rows[$i] ?? new DefaultRow([], $this->context);
            $newRows[$i] = $newRows[$i]->appendRow($rowsToAdd[$i] ?? new DefaultRow([], $this->context));
        }

        // Calculate the maximum width of the new rows

        $width = 0;

        foreach ($newRows as $row) {
            $width = max($width, $row->getWidth());
        }

        // if a row has less width than the maximum width, and it has a single
        // cell, we increase the columnSpan of the cell to fill the gap

        foreach ($newRows as $i => $row) {
            if ($row->getWidth() === $width || \count($row) !== 1) {
                continue;
            }

            $cells = iterator_to_array($row, false);
            $cells[0] = $cells[0]->withColumnSpan($width);
            $newRows[$i] = new DefaultRow($cells, $this->context);
        }

        return new self(array_values($newRows), $this->context);
    }
}
