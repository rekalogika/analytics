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
use Rekalogika\Analytics\Contracts\Exception\EmptyResultException;
use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;

/**
 * @implements \IteratorAggregate<int,DefaultRow>
 */
final readonly class DefaultTable implements Table, \IteratorAggregate
{
    /**
     * Rows without subtotals.
     *
     * @var list<DefaultRow>
     */
    private array $rows;

    /**
     * @param class-string $summaryClass
     * @param iterable<DefaultRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        iterable $rows,
        private RowCollection $rowCollection,
        private ?Expression $condition,
    ) {
        $newRows = [];

        foreach ($rows as $row) {
            if ($row->isSubtotal()) {
                continue;
            }

            $newRows[] = $row;
        }

        $this->rows = $newRows;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getRowPrototype(): DefaultRow
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

    public function getRowCollection(): RowCollection
    {
        return $this->rowCollection;
    }

    public function getCondition(): ?Expression
    {
        return $this->condition;
    }
}
