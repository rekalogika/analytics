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

use Rekalogika\Analytics\Contracts\Row;
use Rekalogika\Analytics\Contracts\Table;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector\UniqueDimensions;

/**
 * @implements \IteratorAggregate<int,DefaultRow>
 */
final readonly class DefaultTable implements Table, \IteratorAggregate
{
    /**
     * @param class-string $summaryClass
     * @param list<DefaultRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        private array $rows,
        private UniqueDimensions $uniqueDimensions,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function first(): ?Row
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

    public function getUniqueDimensions(): UniqueDimensions
    {
        return $this->uniqueDimensions;
    }
}
