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
        private ?Block $generatingBlock,
    ) {}

    public static function createFromCell(
        DefaultCell $cell,
        ?Block $generatingBlock = null,
    ): self {
        return new self([new DefaultRow([$cell], $generatingBlock)], $generatingBlock);
    }

    public function getGeneratingBlock(): ?Block
    {
        return $this->generatingBlock;
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
        return $this->rows[0] ?? new DefaultRow([], null);
    }

    public function getSecondToLastRows(): DefaultRows
    {
        return new self(\array_slice($this->rows, 1), $this->generatingBlock);
    }

    public function appendBelow(DefaultRows $rows): DefaultRows
    {
        return new self([...$this->rows, ...$rows->toArray()], $this->generatingBlock);
    }

    public function appendRight(DefaultRows $rows): DefaultRows
    {
        $height = max($this->getHeight(), $rows->getHeight());
        $rowsToAdd = $rows->toArray();

        $newRows = [];

        for ($i = 0; $i < $height; $i++) {
            $newRows[$i] = $this->rows[$i] ?? new DefaultRow([], null);
            $newRows[$i] = $newRows[$i]->appendRow($rowsToAdd[$i] ?? new DefaultRow([], null));
        }

        return new self(array_values($newRows), $this->generatingBlock);
    }
}
