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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Common\Exception\EmptyResultException;
use Rekalogika\Analytics\Contracts\Result\NormalTable;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;

/**
 * @implements \IteratorAggregate<int,DefaultNormalRow>
 */
final readonly class DefaultNormalTable implements NormalTable, \IteratorAggregate
{
    /**
     * @param class-string $summaryClass
     * @param list<DefaultNormalRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        private array $rows,
        private ItemCollection $itemCollection,
        private RowCollection $rowCollection,
        private ?Expression $condition,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getRowPrototype(): DefaultNormalRow
    {
        $firstKey = array_key_first($this->rows);

        if ($firstKey === null) {
            throw new EmptyResultException('No rows in the table to get prototype from.');
        }

        return $this->rows[$firstKey]
            ?? throw new EmptyResultException('No rows in the table to get prototype from.');
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->rows);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->rows as $row) {
            yield $row;
        }
    }

    public function getItemCollection(): ItemCollection
    {
        return $this->itemCollection;
    }

    public function getRowCollection(): RowCollection
    {
        return $this->rowCollection;
    }

    public function getCondition(): ?Expression
    {
        return $this->condition;
    }
}
