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

namespace Rekalogika\Analytics\PivotTable\Table;

abstract readonly class Cell
{
    final public function __construct(
        private mixed $content,
        private int $columnSpan = 1,
        private int $rowSpan = 1,
    ) {}

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getColumnSpan(): int
    {
        return $this->columnSpan;
    }

    public function withColumnSpan(int $columnSpan): static
    {
        return new static($this->content, $columnSpan, $this->rowSpan);
    }

    public function getRowSpan(): int
    {
        return $this->rowSpan;
    }

    public function withRowSpan(int $rowSpan): static
    {
        return new static($this->content, $this->columnSpan, $rowSpan);
    }

    public function appendRowsRight(Rows $rows): Rows
    {
        $cell = $this->withRowSpan($rows->getHeight());

        $firstRow = (new Row([$cell]))
            ->appendRow($rows->getFirstRow());

        $secondToLastRows = $rows->getSecondToLastRows()->toArray();

        return new Rows([$firstRow, ...$secondToLastRows]);
    }

    public function appendRowsBelow(Rows $rows): Rows
    {
        $cell = $this->withColumnSpan($rows->getWidth());
        $first = new Rows([new Row([$cell])]);

        return $first->appendBelow($rows);
    }

    public function appendCellBelow(Cell $cell): Rows
    {
        $row1 = new Row([$this]);
        $row2 = new Row([$cell]);

        return new Rows([$row1, $row2]);
    }
}
