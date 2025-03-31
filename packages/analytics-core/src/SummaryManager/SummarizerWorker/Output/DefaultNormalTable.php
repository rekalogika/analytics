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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Contracts\Result\NormalTable;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;

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
        private Items $uniqueDimensions,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function first(): ?DefaultNormalRow
    {
        return $this->rows[0] ?? null;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->rows);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->rows;
    }

    public function getUniqueDimensions(): Items
    {
        return $this->uniqueDimensions;
    }
}
