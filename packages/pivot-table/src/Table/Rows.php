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
 * @implements \IteratorAggregate<Row>
 */
final class Rows implements \IteratorAggregate, \Countable
{
    /**
     * @var int<0,max>|null
     */
    private ?int $width = null;

    /**
     * @param list<Row> $rows
     */
    public function __construct(private readonly array $rows = []) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->rows);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->rows);
    }

    /**
     * @return list<Row>
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

    public function getFirstRow(): Row
    {
        return $this->rows[0] ?? new Row([]);
    }

    public function getSecondToLastRows(): Rows
    {
        return new self(\array_slice($this->rows, 1));
    }

    public function appendBelow(Rows $rows): Rows
    {
        return new self([...$this->rows, ...$rows->toArray()]);
    }

    public function appendRight(Rows $rows): Rows
    {
        $height = max($this->getHeight(), $rows->getHeight());
        $rowsToAdd = $rows->toArray();

        $newRows = [];

        for ($i = 0; $i < $height; $i++) {
            $newRows[$i] = $this->rows[$i] ?? new Row([]);
            $newRows[$i] = $newRows[$i]->appendRow($rowsToAdd[$i] ?? new Row([]));
        }

        return new self(array_values($newRows));
    }
}
