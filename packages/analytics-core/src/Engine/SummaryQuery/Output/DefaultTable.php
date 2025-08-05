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

use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\CellRepository;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

/**
 * @implements \IteratorAggregate<Tuple,DefaultCell>
 */
final class DefaultTable implements Table, \IteratorAggregate
{
    private readonly CellRepository $cellRepository;

    /**
     * @var list<string>
     */
    private readonly array $dimensionality;

    /**
     * @var \ArrayObject<int<0,max>,DefaultCell>
     */
    private ?\ArrayObject $rows = null;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(private readonly ResultContext $context)
    {
        $this->cellRepository = $context->getCellRepository();
        $dimensionality = $context->getQuery()->getGroupBy();

        // remove @values from dimensionality
        $this->dimensionality = array_values(array_filter(
            $dimensionality,
            static fn (string $dimension) => $dimension !== '@values',
        ));
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultCell
    {
        if (!$key instanceof DefaultTuple) {
            throw new \InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        return $this->cellRepository->getCellByTuple($key);
    }

    /**
     * @return \ArrayObject<int<0,max>,DefaultCell>
     */
    private function getRows(): \ArrayObject
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $rows = $this->cellRepository
            ->getCellsByDimensionality($this->dimensionality);

        $rows = array_values(iterator_to_array($rows));

        return $this->rows = new \ArrayObject($rows);
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultCell
    {
        return $this->getRows()[$index] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        if (!$key instanceof DefaultTuple) {
            throw new \InvalidArgumentException('This table only supports DefaultTuple as key');
        }

        return $this->cellRepository->hasCellWithTuple($key);
    }

    /**
     * @return class-string
     */
    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->context->getMetadata()->getSummaryClass();
    }

    #[\Override]
    public function first(): ?DefaultCell
    {
        $rows = $this->getRows();

        return $rows[0] ?? null;
    }

    #[\Override]
    public function last(): ?DefaultCell
    {
        $rows = $this->getRows();

        if (($count = $rows->count()) < 1) {
            return null;
        }

        return $rows[$count - 1];
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getRows());
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getRows() as $row) {
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
}
