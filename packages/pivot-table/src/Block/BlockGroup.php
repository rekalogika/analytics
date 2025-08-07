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

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\SubtotalTreeNode;
use Rekalogika\PivotTable\TableFramework\Implementation\DefaultTreeNode;
use Rekalogika\PivotTable\Util\ItemToTreeNodeMap;

abstract class BlockGroup extends Block
{
    public function __construct(
        private readonly TreeNode $node,
        private readonly ?string $childKey,
        BlockContext $context,
    ) {
        parent::__construct($context);
    }

    protected function getNode(): TreeNode
    {
        return $this->node;
    }

    protected function getChildKey(): string
    {
        if ($this->childKey === null) {
            throw new \RuntimeException('Child key is not set.');
        }

        return $this->childKey;
    }

    protected function tryGetChildKey(): ?string
    {
        return $this->childKey;
    }

    /**
     * @param list<TreeNode> $nodes
     * @param non-empty-list<TreeNode> $prototypeNodes
     * @return non-empty-list<TreeNode>
     */
    protected function balanceTreeNodesWithPrototype(
        array $nodes,
        array $prototypeNodes,
    ): array {
        $childKey = $this->getChildKey();
        $node = $this->getNode();

        // create a map of children items to nodes
        $itemToNodes = ItemToTreeNodeMap::create($nodes);

        // create result
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($prototypeNodes as $prototype) {
            $currentItem = $prototype->getItemByKey($childKey);

            if ($itemToNodes->exists($currentItem)) {
                $result[] = $itemToNodes->get($currentItem);
            } else {
                // $null = NullTreeNode::fromInterface($prototype);
                $null = $node->createNullChild(
                    childKey: $childKey,
                    childItem: $currentItem,
                );

                $result[] = $null;
            }
        }

        return $result;
    }

    protected function getSubtotalNode(): ?TreeNode
    {
        $childKey = $this->getChildKey();

        // different values cannot be aggregated
        if ($childKey === '@values') {
            return null;
        }

        // If subtotals are not desired for this node, return null.
        if ($this->getContext()->doCreateSubtotals($childKey) === false) {
            return null;
        }

        // return new SubtotalTreeNode(
        //     node: $this->node,
        //     childrenKey: $childKey,
        //     isLeaf: $this->getContext()->isLeaf($childKey),
        // );

        return $this->node->createSubtotal($childKey);
    }


    /**
     * @param null|non-empty-list<TreeNode> $prototypeNodes
     * @return iterable<TreeNode>
     */
    protected function getChildTreeNodes(?array $prototypeNodes = null): iterable
    {
        if ($this->childKey === null) {
            return [];
        }

        $children = $this->node->drillDown($this->childKey);
        $children = iterator_to_array($children, false);

        if ($prototypeNodes !== null) {
            $children = $this->balanceTreeNodesWithPrototype(
                nodes: $children,
                prototypeNodes: $prototypeNodes,
            );
        }

        if (\count($children) >= 2) {
            $subtotalNode = $this->getSubtotalNode();

            if ($subtotalNode !== null) {
                $children[] = $subtotalNode;
            }
        }

        return $children;
    }

    /**
     * @param null|non-empty-list<TreeNode> $prototypeNodes
     */
    protected function getOneChildTreeNode(?array $prototypeNodes = null): TreeNode
    {
        foreach ($this->getChildTreeNodes($prototypeNodes) as $childNode) {
            return $childNode;
        }

        throw new \RuntimeException('No child nodes found in the current node.');
    }

    /**
     * @param null|non-empty-list<TreeNode> $prototypeNodes
     * @return iterable<Block>
     */
    protected function getChildBlocks(?array $prototypeNodes = null): iterable
    {
        $children = $this->getChildTreeNodes($prototypeNodes);

        if ($children === []) {
            yield new EmptyBlockGroup(
                node: $this->getNode(),
                childKey: null,
                context: $this->getContext(),
            );
        }

        foreach ($children as $childNode) {
            yield $this->createBlock($childNode);
        }
    }

    /**
     * @param null|non-empty-list<TreeNode> $prototypeNodes
     */
    protected function getOneChildBlock(?array $prototypeNodes = null): Block
    {
        foreach ($this->getChildBlocks($prototypeNodes) as $childBlock) {
            return $childBlock;
        }

        throw new \RuntimeException('No child blocks found in the current node.');
    }
}
