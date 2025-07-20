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

abstract readonly class DefaultCell implements Cell
{
    final public function __construct(
        private string $name,
        private mixed $content,
        private ?Block $generatingBlock,
        private int $columnSpan = 1,
        private int $rowSpan = 1,
    ) {}

    public function getGeneratingBlock(): ?Block
    {
        return $this->generatingBlock;
    }

    final public function getKey(): string
    {
        return $this->name;
    }

    #[\Override]
    final public function getContent(): mixed
    {
        return $this->content;
    }

    #[\Override]
    final public function getColumnSpan(): int
    {
        return $this->columnSpan;
    }

    final public function withColumnSpan(int $columnSpan): static
    {
        return new static(
            name: $this->name,
            content: $this->content,
            columnSpan: $columnSpan,
            rowSpan: $this->rowSpan,
            generatingBlock: $this->generatingBlock,
        );
    }

    #[\Override]
    final public function getRowSpan(): int
    {
        return $this->rowSpan;
    }

    final public function withRowSpan(int $rowSpan): static
    {
        return new static(
            name: $this->name,
            content: $this->content,
            columnSpan: $this->columnSpan,
            rowSpan: $rowSpan,
            generatingBlock: $this->generatingBlock,
        );
    }

    final public function appendRowsRight(DefaultRows $rows): DefaultRows
    {
        $cell = $this->withRowSpan($rows->getHeight());

        $firstRow = (new DefaultRow([$cell], $this->generatingBlock))
            ->appendRow($rows->getFirstRow());

        $secondToLastRows = $rows->getSecondToLastRows()->toArray();

        return new DefaultRows([$firstRow, ...$secondToLastRows], $this->generatingBlock);
    }

    final public function appendRowsBelow(DefaultRows $rows): DefaultRows
    {
        $cell = $this->withColumnSpan($rows->getWidth());
        $first = new DefaultRows(
            [new DefaultRow([$cell], $this->generatingBlock)],
            $this->generatingBlock,
        );

        return $first->appendBelow($rows);
    }

    final public function appendCellBelow(DefaultCell $cell): DefaultRows
    {
        $row1 = new DefaultRow([$this], $this->generatingBlock);
        $row2 = new DefaultRow([$cell], $this->generatingBlock);

        return new DefaultRows([$row1, $row2], $this->generatingBlock);
    }

    private function getSignature(): string
    {
        return hash('xxh128', serialize($this));
    }

    final public function isSameAs(DefaultCell $cell): bool
    {
        return $this->getSignature() === $cell->getSignature();
    }
}
