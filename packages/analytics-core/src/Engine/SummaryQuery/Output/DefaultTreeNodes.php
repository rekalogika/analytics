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

use Rekalogika\Analytics\Contracts\Result\TreeNodes;

/**
 * @implements \IteratorAggregate<mixed,DefaultTree>
 */
final class DefaultTreeNodes implements TreeNodes, \IteratorAggregate
{
    /**
     * @param list<DefaultTree> $treeNodes
     */
    public function __construct(
        private array $treeNodes = [],
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->treeNodes as $key => $treeNode) {
            yield $treeNode->getMember() => $treeNode;
        }
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultTree
    {
        foreach ($this->treeNodes as $treeNode) {
            if ($treeNode->getMember() === $key) {
                return $treeNode;
            }
        }

        return null;
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultTree
    {
        return $this->treeNodes[$index] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        foreach ($this->treeNodes as $treeNode) {
            if ($treeNode->getMember() === $key) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function first(): ?DefaultTree
    {
        return $this->treeNodes[0] ?? null;
    }

    #[\Override]
    public function last(): ?DefaultTree
    {
        $count = \count($this->treeNodes);

        return $count > 0 ? $this->treeNodes[$count - 1] : null;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->treeNodes);
    }
}
