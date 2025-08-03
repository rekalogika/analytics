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
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;

final class TreeNodeDecorator extends BaseTreeNodeDecorator
{
    /**
     * @var array<int,list<self>>
     */
    private array $grandChildrenItems = [];

    /**
     * @var array<int,list<self>>
     */
    private array $children = [];

    private readonly self $root;

    /**
     * @var int<0,max>
     */
    private int $depth;

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
            $this->depth = 0;
        } else {
            $current = $this;
            $depth = 0;

            while ($current->parent !== null) {
                $current = $current->parent;
                $depth++;
            }

            $this->root = $current;
            $this->depth = $depth + 1;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @todo implement manual traversal for databases that don't have rollups or
     * cubes
     *
     * @param int<1,max> $level
     * @return list<self>
     */
    private function getGrandchildren(int $level = 1): array
    {
        if (isset($this->grandChildrenItems[$level])) {
            return $this->grandChildrenItems[$level];
        }

        return $this->grandChildrenItems[$level] = $this->getChildren($level + 1);
    }

    /**
     * @param int<1,max> $level
     * @return non-empty-list<self>
     */
    public function getBalancedChildren(int $level = 1): array
    {
        if ($this->parent === null) {
            // If this is the root node, return the children directly
            $result = $this->getChildren($level);

            if ($result === []) {
                throw new \LogicException('Root node must have at least one child.');
            }

            return $result;
        }

        $children = $this->getChildren($level);
        // $parentGrandChildren = $this->parent->getGrandchildren($level);
        $parentGrandChildren = $this->root->getGrandchildren($this->depth + $level);

        // create a map of children items to nodes
        $childrenItemsToNodes = ItemToTreeNodeDecoratorMap::create($children);

        // create result
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($parentGrandChildren as $node) {
            $item = $node->getItem();

            if ($childrenItemsToNodes->exists($item)) {
                $result[] = $childrenItemsToNodes->get($item);
            } else {
                $null = NullTreeNode::fromInterface($node);
                $decorated = $this->repository->decorate($null, $this);
                $result[] = $decorated;
            }
        }

        /** @var non-empty-list<self> */
        return $result;
    }
}
