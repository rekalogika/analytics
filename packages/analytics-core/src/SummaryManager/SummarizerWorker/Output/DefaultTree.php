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

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final class DefaultTree implements TreeNode, \IteratorAggregate
{
    use NodeTrait;
    use BalancedTreeChildrenTrait;

    /**
     * @param class-string $summaryClass
     * @param list<DefaultTreeNode> $children
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly TranslatableInterface $label,
        private readonly ?string $childrenKey,
        private readonly array $children,
        private readonly Items $items,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
    ) {
        if ($childrenKey === null && $children !== []) {
            throw new UnexpectedValueException('Children key cannot be null if children is not empty');
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
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return new DefaultTuple([]);
    }

    #[\Override]
    public function getMeasure(): ?DefaultMeasure
    {
        return null;
    }

    #[\Override]
    public function isNull(): bool
    {
        return false;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getKey(): string
    {
        return '';
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
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
