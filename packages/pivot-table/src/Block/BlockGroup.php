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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Decorator\Internal\ItemToTreeNodeDecoratorMap;
use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\SubtotalTreeNode;

abstract class BlockGroup extends Block
{
    /**
     * @var array<int,list<TreeNodeDecorator>>
     */
    private array $childNodes = [];

    /**
     * @var array<int,non-empty-list<TreeNodeDecorator>>
     */
    private array $balancedChildNodes = [];

    /**
     * @var array<int,list<Block>>
     */
    private array $childBlocks = [];

    /**
     * @var array<int,list<Block>>
     */
    private array $balancedChildBlocks = [];

    public function __construct(
        private readonly TreeNodeDecorator $node,
        private readonly ?TreeNodeDecorator $parentNode,
        BlockContext $context,
    ) {
        parent::__construct($context);
    }

    /**
     * @param int<1,max> $level
     * @return list<Block>
     */
    public function getChildBlocks(int $level = 1): array
    {
        if (isset($this->childBlocks[$level])) {
            return $this->childBlocks[$level];
        }

        $childBlocks = [];

        foreach ($this->getChildNodes($level) as $childNode) {
            $childBlocks[] = $this->createBlock(
                node: $childNode,
                parentNode: $this->node,
                levelIncrement: $level,
                key: $childNode->getKey(),
            );
        }

        return $this->childBlocks[$level] = $childBlocks;
    }

    /**
     * @param int<1,max> $level
     * @return list<Block>
     */
    public function getBalancedChildBlocks(int $level = 1): array
    {
        if (isset($this->balancedChildBlocks[$level])) {
            return $this->balancedChildBlocks[$level];
        }

        $balancedChildBlocks = [];

        foreach ($this->getBalancedChildNodes($level) as $childNode) {
            $balancedChildBlocks[] = $this->createBlock(
                node: $childNode,
                parentNode: $this->node,
                levelIncrement: $level,
                key: $childNode->getKey(),
            );
        }

        return $this->balancedChildBlocks[$level] = $balancedChildBlocks;
    }

    /**
     * @param int<1,max> $level
     */
    public function getOneChildBlock(int $level = 1): Block
    {
        return $this->getChildBlocks($level)[0]
            ?? throw new \RuntimeException('No child blocks found in the current node.');
    }

    /**
     * @param int<1,max> $level
     */
    public function getOneBalancedChildBlock(int $level = 1): Block
    {
        return $this->getBalancedChildBlocks($level)[0]
            ?? throw new \RuntimeException('No child blocks found in the current node.');
    }

    final public function getNode(): TreeNodeDecorator
    {
        return $this->node;
    }

    final public function getParentNode(): ?TreeNodeDecorator
    {
        return $this->parentNode;
    }

    /**
     * @param int<1,max> $level
     */
    protected function getSubtotalNode(int $level = 1): ?TreeNodeDecorator
    {
        $balancedChildren = $this->node->getBalancedChildren($level, $this->getLevel());
        $child = $balancedChildren[0] ?? null;

        // If subtotals are not desired for this node, return null.
        if ($child === null || $this->getContext()->doCreateSubtotals($child) === false) {
            return null;
        }

        // different values cannot be aggregated
        if ($child->getKey() === '@values') {
            return null;
        }

        $subtotalNode = new SubtotalTreeNode(
            node: $this->node,
            childrenKey: $child->getKey(),
            isLeaf: $child->isLeaf(),
            level: $level,
        );

        return $this
            ->getContext()
            ->getRepository()
            ->decorate($subtotalNode)
            ->withParent($this->node);
    }

    /**
     * @param int<1,max> $level
     * @return list<TreeNodeDecorator>
     */
    private function getChildNodes(int $level = 1): array
    {
        if (isset($this->childNodes[$level])) {
            return $this->childNodes[$level];
        }

        $children = $this->node->getChildren($level);

        if (\count($children) >= 2) {
            $subtotalNode = $this->getSubtotalNode($level);

            if ($subtotalNode !== null) {
                $children[] = $subtotalNode;
            }
        }

        return $this->childNodes[$level] = $children;

        // $key = $this->getContext()->getNextKey($level);

        // if ($key === null) {
        //     return [];
        // }

        // return $this->getChildNodesByKey($key);
    }


    /**
     * @param int<1,max> $level
     * @return non-empty-list<TreeNodeDecorator>
     */
    private function getBalancedChildNodes(int $level = 1): array
    {
        if (isset($this->balancedChildNodes[$level])) {
            return $this->balancedChildNodes[$level];
        }

        $children = $this->node->getBalancedChildren($level, $this->getLevel());

        $subtotalNode = $this->getSubtotalNode($level);

        if ($subtotalNode !== null) {
            $children[] = $subtotalNode;
        }

        /** @var non-empty-list<TreeNodeDecorator> $children */
        return $this->balancedChildNodes[$level] = $children;
    }

    /**
     * @param int<1,max> $level
     */
    final public function getOneChild(int $level = 1): TreeNodeDecorator
    {
        return $this->getChildNodes($level)[0]
            ?? $this->getBalancedChildNodes($level)[0]
            ?? throw new \RuntimeException('No child nodes found in the current node.');
    }

    /**
     * @param list<TreeNodeDecorator> $nodes
     * @param non-empty-list<TreeNodeDecorator> $prototypeNodes
     * @return list<TreeNodeDecorator>
     */
    protected function balanceNodesWithPrototype(
        array $nodes,
        array $prototypeNodes,
    ): array {
        // create a map of children items to nodes
        $itemToNodes = ItemToTreeNodeDecoratorMap::create($nodes);

        // create result
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($prototypeNodes as $prototype) {
            $currentItem = $prototype->getItem();

            if ($itemToNodes->exists($currentItem)) {
                $result[] = $itemToNodes
                    ->get($currentItem)
                    ->withParent($this->getNode());
            } else {
                $null = NullTreeNode::fromInterface($prototype);

                $decorated = $this->getContext()->getRepository()
                    ->decorate($null)
                    ->withParent($this->getNode());

                $result[] = $decorated;
            }
        }

        return $result;
    }


    /**
     * @param null|non-empty-list<TreeNodeDecorator> $prototypeNodes
     * @return list<TreeNodeDecorator>
     */
    private function getChildNodesByKey(string $key, ?array $prototypeNodes = null): array
    {
        $children = $this->node->drillDown($key);

        if ($prototypeNodes !== null) {
            $children = $this->balanceNodesWithPrototype(
                nodes: $children,
                prototypeNodes: $prototypeNodes,
            );
        }

        // if (\count($children) >= 2) {
        //     $subtotalNode = $this->getSubtotalNode($level);

        //     if ($subtotalNode !== null) {
        //         $children[] = $subtotalNode;
        //     }
        // }

        return $children;
    }
}
