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

namespace Rekalogika\PivotTable\Decorator;

use Rekalogika\PivotTable\Block\SubtotalTreeNode;
use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Decorator\Internal\ItemToTreeNodeDecoratorMap;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;

final class TreeNodeDecorator extends BaseTreeNodeDecorator
{
    /**
     * @var array<int,list<self>>
     */
    private array $children = [];

    private readonly self $root;

    public static function decorate(TreeNode $node): self
    {
        $repository = new TreeNodeDecoratorRepository();

        return $repository->decorate($node, null);
    }

    public function __construct(
        private readonly TreeNode $node,
        private readonly null|self $parent,
        private readonly TreeNodeDecoratorRepository $repository,
    ) {
        if ($node instanceof self) {
            throw new \InvalidArgumentException('Cannot redecorate a TreeNodeDecorator instance.');
        }

        if ($parent === null) {
            $this->root = $this;
        } else {
            $current = $this;

            while ($current->parent !== null) {
                $current = $current->parent;
            }

            $this->root = $current;
        }

        parent::__construct($node);
    }

    public function getRoot(): self
    {
        return $this->root;
    }

    public function isSubtotal(): bool
    {
        return $this->node instanceof SubtotalTreeNode;
    }

    /**
     * @param int<1,max> $level
     * @return list<self>
     */
    #[\Override]
    public function getChildren(int $level = 1): array
    {
        if (isset($this->children[$level])) {
            return $this->children[$level];
        }

        $result = [];

        foreach ($this->node->getChildren($level) as $child) {
            $result[] = $this->repository->decorate($child, $this);
        }

        return $this->children[$level] = $result;
    }

    /**
     * Gets the unique child items from the perspective of the parent node.
     *
     * @param int<1,max> $childLevel 1 means the immediate children, 2 means
     * grandchildren, etc.
     * @param int<0,max> $parentLevel 0 means the current node, 1 means the parent node,
     * etc.
     * @return list<self>
     */
    private function getChildrenSeenByParent(int $childLevel, int $parentLevel): array
    {
        $parent = $this->getParentByLevel($parentLevel);

        return $parent->getChildren($childLevel + $parentLevel);
    }

    /**
     * @param int<1,max> $childLevel 1 means the immediate children, 2 means
     * grandchildren, etc.
     * @param int<0,max> $parentLevel 0 means the current node, 1 means the parent node,
     * etc.
     * @return list<self>
     */
    public function getBalancedChildren(int $childLevel, int $parentLevel): array
    {
        $children = $this->getChildren($childLevel);
        $childrenSeenByParent = $this->getChildrenSeenByParent($childLevel, $parentLevel);

        // create a map of children items to nodes
        $childrenItemsToNodes = ItemToTreeNodeDecoratorMap::create($children);

        // create result
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($childrenSeenByParent as $child) {
            $currentItem = $child->getItem();

            if ($childrenItemsToNodes->exists($currentItem)) {
                $result[] = $childrenItemsToNodes->get($currentItem);
            } else {
                $null = NullTreeNode::fromInterface($child);
                $decorated = $this->repository->decorate($null, $this);
                $result[] = $decorated;
            }
        }

        /** @var non-empty-list<self> */
        return $result;
    }

    /**
     * Gets the parent node at the specified level.
     *
     * @param int<0,max> $level The level of the parent node to retrieve. 0
     * means the current node, 1 means the immediate parent, 2 means the
     * grandparent, etc.
     */
    private function getParentByLevel(int $level): self
    {
        $current = $this;

        for ($i = 1; $i <= $level; $i++) {
            if ($current->parent === null) {
                throw new \LogicException('Cannot get parent by level: no parent found at level ' . $level);
            }
            $current = $current->parent;
        }

        return $current;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
