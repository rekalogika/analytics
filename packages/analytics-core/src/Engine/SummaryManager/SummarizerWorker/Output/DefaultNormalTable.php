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

use Rekalogika\Analytics\Common\Exception\EmptyResultException;
use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\NormalTable;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;

/**
 * @implements \IteratorAggregate<int,DefaultNormalRow>
 */
final readonly class DefaultNormalTable implements NormalTable, \IteratorAggregate
{
    /**
     * Rows without subtotals.
     *
     * @var array<string,DefaultNormalRow>
     */
    private array $rows;

    /**
     * All rows including subtotals.
     *
     * @var array<string,DefaultNormalRow>
     */
    private array $allRows;

    /**
     * @param class-string $summaryClass
     * @param list<DefaultNormalRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        array $rows,
        private ItemCollection $itemCollection,
    ) {
        $newRows = [];
        $newAllRows = [];

        foreach ($rows as $row) {
            $signature = $row->getSignature();

            if (isset($newRows[$signature])) {
                throw new LogicException(
                    \sprintf('Row with signature "%s" already exists.', $signature),
                );
            }

            if (!$row->isSubtotal()) {
                $newRows[$signature] = $row;
            }

            $newAllRows[$signature] = $row;
        }

        $this->rows = $newRows;
        $this->allRows = $newAllRows;
    }

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

    public function getRowByTuple(DefaultTuple $tuple): ?DefaultNormalRow
    {
        $signature = $tuple->getSignature();

        return $this->allRows[$signature] ?? null;
    }
}
