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

use Rekalogika\Analytics\PivotTable\TreeNode;

abstract readonly class Cell
{
    final public function __construct(
        private ContentType $type,
        private string $key,
        private mixed $content,
        private TreeNode $treeNode,
        private int $columnSpan = 1,
        private int $rowSpan = 1,
    ) {}

    final public function getType(): ContentType
    {
        return $this->type;
    }

    final public function getKey(): string
    {
        return $this->key;
    }

    final public function getContent(): mixed
    {
        return $this->content;
    }

    final public function getTreeNode(): TreeNode
    {
        return $this->treeNode;
    }

    final public function getColumnSpan(): int
    {
        return $this->columnSpan;
    }

    final public function withColumnSpan(int $columnSpan): static
    {
        return new static(
            type: $this->type,
            key: $this->key,
            content: $this->content,
            treeNode: $this->treeNode,
            columnSpan: $columnSpan,
            rowSpan: $this->rowSpan,
        );
    }

    final public function getRowSpan(): int
    {
        return $this->rowSpan;
    }

    final public function withRowSpan(int $rowSpan): static
    {
        return new static(
            type: $this->type,
            key: $this->key,
            content: $this->content,
            treeNode: $this->treeNode,
            columnSpan: $this->columnSpan,
            rowSpan: $rowSpan,
        );
    }

    final public function appendRowsRight(Rows $rows): Rows
    {
        $cell = $this->withRowSpan($rows->getHeight());

        $firstRow = (new Row([$cell]))
            ->appendRow($rows->getFirstRow());

        $secondToLastRows = $rows->getSecondToLastRows()->toArray();

        return new Rows([$firstRow, ...$secondToLastRows]);
    }

    final public function appendRowsBelow(Rows $rows): Rows
    {
        $cell = $this->withColumnSpan($rows->getWidth());
        $first = new Rows([new Row([$cell])]);

        return $first->appendBelow($rows);
    }

    final public function appendCellBelow(Cell $cell): Rows
    {
        $row1 = new Row([$this]);
        $row2 = new Row([$cell]);

        return new Rows([$row1, $row2]);
    }
}
