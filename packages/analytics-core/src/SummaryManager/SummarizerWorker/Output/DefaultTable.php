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

use Rekalogika\Analytics\Contracts\Result\Table;

/**
 * @implements \IteratorAggregate<int,DefaultRow>
 */
final readonly class DefaultTable implements Table, \IteratorAggregate
{
    /**
     * @param list<DefaultRow> $rows
     */
    public function __construct(
        private array $rows,
    ) {}

    #[\Override]
    public function first(): ?DefaultRow
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
}
