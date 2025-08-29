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

use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Result\Table;
use Rekalogika\Analytics\Engine\SourceEntities\SourceEntitiesFactory;
use Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory\CellRepository;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;
use Rekalogika\Analytics\Engine\SummaryQuery\Registry\RowRegistry;

/**
 * @implements \IteratorAggregate<Coordinates,DefaultRow>
 */
final class DefaultTable implements Table, \IteratorAggregate
{
    private readonly CellRepository $cellRepository;

    /**
     * @var \ArrayObject<int<0,max>,DefaultRow>|null
     * @phpstan-ignore property.unusedType
     */
    private ?\ArrayObject $rows = null;

    /**
     * @param list<string> $dimensionality
     */
    public static function create(
        ResultContext $context,
        array $dimensionality,
        SourceEntitiesFactory $sourceEntitiesFactory,
    ): self {
        $registry = new RowRegistry(
            dimensionality: $dimensionality,
            sourceEntitiesFactory: $sourceEntitiesFactory,
        );

        return new self(
            context: $context,
            dimensionality: $dimensionality,
            registry: $registry,
        );
    }

    /**
     * @param list<string> $dimensionality
     */
    public function __construct(
        private readonly ResultContext $context,
        private readonly array $dimensionality,
        private readonly RowRegistry $registry,
    ) {
        $this->cellRepository = $context->getCellRepository();
    }

    #[\Override]
    public function get(mixed $key): DefaultRow
    {
        if (!$key instanceof DefaultCoordinates) {
            throw new \InvalidArgumentException('This table only supports DefaultCoordinates as key');
        }

        $cell = $this->cellRepository->getCellByCoordinates($key);

        return $this->registry->getRowByCell($cell);
    }

    /**
     * @return \ArrayObject<int<0,max>,DefaultRow>
     */
    private function getRows(): \ArrayObject
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $cells = $this->cellRepository
            ->getCellsByDimensionality($this->dimensionality);

        $rows = [];

        foreach ($cells as $cell) {
            $rows[] = $this->registry->getRowByCell($cell);
        }

        /**
         * @var \ArrayObject<int<0,max>,DefaultRow>
         */
        return $this->rows = new \ArrayObject($rows, \ArrayObject::ARRAY_AS_PROPS); // @phpstan-ignore-line
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultRow
    {
        return $this->getRows()[$index] ?? null;
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        if (!$key instanceof DefaultCoordinates) {
            throw new \InvalidArgumentException('This table only supports DefaultCoordinates as key');
        }

        return $this->cellRepository->hasCellWithCoordinates($key);
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
    public function first(): ?DefaultRow
    {
        $rows = $this->getRows();

        return $rows[0] ?? null;
    }

    #[\Override]
    public function last(): ?DefaultRow
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
            yield $row->getCoordinates() => $row;
        }
    }
}
