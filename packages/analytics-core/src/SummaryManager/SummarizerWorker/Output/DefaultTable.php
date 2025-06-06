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
use Rekalogika\Analytics\Exception\EmptyResultException;
use Rekalogika\Analytics\Exception\LogicException;

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
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getRowPrototype(): DefaultRow
    {
        return $this->rows[0]
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
        yield from $this->rows;
    }
}
