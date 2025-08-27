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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\CubeCells;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

/**
 * @implements \IteratorAggregate<DefaultCoordinates,DefaultCell>
 */
final class DefaultCells implements CubeCells, \IteratorAggregate
{
    /**
     * @var null|\ArrayObject<string,DefaultCell> $cubeCells
     */
    private ?\ArrayObject $cubeCells = null;

    public function __construct(
        private DefaultCell $baseCell,
        private string $childDimensionName,
        private ResultContext $context,
    ) {}

    /**
     * @return \ArrayObject<string,DefaultCell>
     */
    private function getResult(): \ArrayObject
    {
        if ($this->cubeCells !== null) {
            return $this->cubeCells;
        }

        $cells = $this->context
            ->getCellRepository()
            ->getCellsByBaseAndDimensionName(
                baseCell: $this->baseCell,
                dimensionName: $this->childDimensionName,
                fillGaps: true,
            );

        $newCells = [];

        foreach ($cells as $cell) {
            $newCells[$cell->getCoordinates()->getSignature()] = $cell;
        }

        return $this->cubeCells = new \ArrayObject($newCells);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getResult() as $cell) {
            yield $cell->getCoordinates() => $cell;
        }
    }

    #[\Override]
    public function get(mixed $key): ?DefaultCell
    {
        if (!$key instanceof DefaultCoordinates) {
            throw new InvalidArgumentException('This class only accepts DefaultCordinates as key.');
        }

        $signature = $key->getSignature();

        return $this->getResult()->offsetGet($signature);
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultCell
    {
        $i = 0;

        foreach ($this->getResult() as $cell) {
            if ($i === $index) {
                return $cell;
            }
            $i++;
        }

        return null;
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        if (!$key instanceof DefaultCoordinates) {
            throw new InvalidArgumentException('This class only accepts DefaultCoordinates as key.');
        }

        $signature = $key->getSignature();

        return $this->getResult()->offsetExists($signature);
    }

    #[\Override]
    public function first(): ?DefaultCell
    {
        foreach ($this->getResult() as $cell) {
            return $cell;
        }

        return null;
    }

    #[\Override]
    public function last(): ?DefaultCell
    {
        $count = \count($this->getResult());

        if ($count === 0) {
            return null;
        }

        $lastIndex = $count - 1;

        return $this->getByIndex($lastIndex);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getResult());
    }
}
