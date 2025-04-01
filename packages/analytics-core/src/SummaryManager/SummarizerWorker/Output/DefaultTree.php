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

use Rekalogika\Analytics\Contracts\Result\Tree;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final class DefaultTree implements Tree, \IteratorAggregate
{
    use NodeTrait;
    use BalancedTreeChildrenTrait;

    /**
     * @param list<DefaultTreeNode> $children
     */
    public function __construct(
        private readonly ?string $childrenKey,
        private readonly array $children,
        private readonly Items $items,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
    ) {
        if ($childrenKey === null) {
            if ($children !== []) {
                throw new UnexpectedValueException('Children key cannot be null if children is not empty');
            }
        }

        foreach ($children as $child) {
            if ($child->getKey() !== $childrenKey) {
                throw new UnexpectedValueException(
                    \sprintf('Invalid child key "%s", expected "%s"', $child->getKey(), get_debug_type($childrenKey)),
                );
            }
        }
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getBalancedChildren() as $child) {
            yield $child->getMember() => $child;
        }
    }

    public function getUniqueDimensions(): Items
    {
        return $this->items;
    }

    public function getChildrenKey(): ?string
    {
        return $this->childrenKey;
    }
}
