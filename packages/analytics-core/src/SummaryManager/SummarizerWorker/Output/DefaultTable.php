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

use Rekalogika\Analytics\Query\Table;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\ResultRow;

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

    /**
     * @param iterable<ResultRow> $resultRows
     */
    public static function fromResultRows(iterable $resultRows): self
    {
        /**
         * @psalm-suppress InvalidArgument
         */
        $resultRows = iterator_to_array($resultRows);

        $resultRows = array_values(array_filter(
            $resultRows,
            static fn(ResultRow $resultRow): bool => !$resultRow->isSubtotal(),
        ));

        $rows = array_map(
            static fn(ResultRow $resultRow): DefaultRow => DefaultRow::createFromResultRow($resultRow),
            $resultRows,
        );

        return new self(
            rows: $rows,
        );
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->rows;
    }
}
