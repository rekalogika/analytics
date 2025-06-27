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
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\TableVisitor;

/**
 * @implements \IteratorAggregate<Cell>
 */
final readonly class DefaultRow implements \IteratorAggregate, Row
{
    /**
     * @param list<DefaultCell> $cells
     */
    public function __construct(
        private array $cells = [],
    ) {}

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
        return new self([...$this->cells, $cell]);
    }

    public function appendRow(DefaultRow $row): static
    {
        return new self([...$this->cells, ...$row->cells]);
    }
}
