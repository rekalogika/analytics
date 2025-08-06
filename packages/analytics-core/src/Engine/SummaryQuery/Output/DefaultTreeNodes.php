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

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\TreeNodes;
use Rekalogika\Analytics\Engine\SummaryQuery\Registry\TreeNodeRegistry;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 */
final readonly class DefaultTreeNodes implements TreeNodes, \IteratorAggregate
{
    public function __construct(
        private DefaultCells $cells,
        private DimensionNames $dimensionNames,
        private TreeNodeRegistry $registry,
    ) {}

    #[\Override]
    public function getByKey(mixed $key): mixed
    {
        $current = $this->dimensionNames->getCurrent();

        if ($current === null) {
            throw new LogicException('Cannot get by key when current dimension is null.');
        }

        foreach ($this as $node) {
            if ($node->getTuple()->getByKey($current)?->getRawMember() === $key) {
                return $node;
            }
        }

        return null;
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $result = $this->cells->getByIndex($index);

        if ($result === null) {
            return null;
        }

        return $this->registry->get(
            cell: $result,
            dimensionNames: $this->dimensionNames,
        );
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        $current = $this->dimensionNames->getCurrent();

        if ($current === null) {
            throw new LogicException('Cannot get by key when current dimension is null.');
        }

        foreach ($this as $node) {
            if ($node->getTuple()->getByKey($current)?->getRawMember() === $key) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function first(): mixed
    {
        $result = $this->cells->first();

        if ($result === null) {
            return null;
        }

        return $this->registry->get(
            cell: $result,
            dimensionNames: $this->dimensionNames,
        );
    }

    #[\Override]
    public function last(): mixed
    {
        $result = $this->cells->last();

        if ($result === null) {
            return null;
        }

        return $this->registry->get(
            cell: $result,
            dimensionNames: $this->dimensionNames,
        );
    }

    #[\Override]
    public function count(): int
    {
        return $this->cells->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        /** @psalm-suppress MixedArgument */
        foreach ($this->cells as $cell) {
            yield $this->registry->get(
                cell: $cell,
                dimensionNames: $this->dimensionNames,
            );
        }
    }
}
