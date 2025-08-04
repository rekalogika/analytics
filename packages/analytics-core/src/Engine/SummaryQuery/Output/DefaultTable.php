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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Contracts\Result\Cube;
use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

/**
 * @implements \IteratorAggregate<Tuple,DefaultRow>
 */
final readonly class DefaultTable implements Table, Cube, \IteratorAggregate
{
    /**
     * Non-grouping rows
     *
     * @var array<string,DefaultRow>
     */
    private array $rows;

    /**
     * @param class-string $summaryClass
     * @param iterable<DefaultRow> $rows
     */
    public function __construct(
        private string $summaryClass,
        iterable $rows,
        private ResultContext $context,
    ) {
        $newRows = [];

        foreach ($rows as $row) {
            $newRows[$row->getSignature()] = $row;
        }

        $this->rows = $newRows;
    }

    public function withoutGroupingRows(): self
    {
        $rows = (function (): \Traversable {
            foreach ($this->rows as $row) {
                if (!$row->isGrouping()) {
                    yield $row;
                }
            }
        })();

        return new self(
            summaryClass: $this->summaryClass,
            rows: $rows,
            context: $this->context,
        );
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultRow
    {
        if (!$key instanceof DefaultTuple) {
            throw new \InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        $signature = $key->getSignature();

        return $this->rows[$signature] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultRow
    {
        $keys = array_keys($this->rows);

        if (!isset($keys[$index])) {
            return null;
        }

        $signature = $keys[$index];

        return $this->rows[$signature] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        if (!$key instanceof DefaultTuple) {
            throw new \InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        $signature = $key->getSignature();

        return isset($this->rows[$signature]);
    }

    /**
     * @return class-string
     */
    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function first(): ?DefaultRow
    {
        $firstKey = array_key_first($this->rows);

        if ($firstKey === null) {
            return null;
        }

        return $this->rows[$firstKey];
    }

    #[\Override]
    public function last(): ?DefaultRow
    {
        $lastKey = array_key_last($this->rows);

        if ($lastKey === null) {
            return null;
        }

        return $this->rows[$lastKey];
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
            yield $row->getTuple() => $row;
        }
    }

    public function getMeasureByTuple(DefaultTuple $tuple): ?DefaultMeasure
    {
        $measureName = $tuple->getMeasureName();

        if ($measureName === null) {
            return null;
        }

        $row = $this->getByKey($tuple->withoutMeasure());

        if ($row === null) {
            return null;
        }

        return $row->getMeasures()->getByKey($measureName);
    }

    public function getContext(): ResultContext
    {
        return $this->context;
    }
}
