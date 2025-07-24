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

use Rekalogika\PivotTable\Contracts\Tree\BranchNode;
use Rekalogika\PivotTable\Contracts\Tree\LeafNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

abstract class BlockGroup extends Block
{
    /**
     * @var list<TreeNode>|null
     */
    private ?array $children = null;

    /**
     * @var non-empty-list<TreeNode>|null
     */
    private ?array $balancedChildren = null;

    /**
     * @var list<Block>|null
     */
    private ?array $childBlocks = null;

    /**
     * @var list<Block>|null
     */
    private ?array $balancedChildBlocks = null;

    public function __construct(
        private readonly BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);
    }

    /**
     * @param list<LeafNode> $leafNodes
     */
    abstract public function getSubtotalHeaderRows(array $leafNodes): DefaultRows;

    /**
     * @param list<LeafNode> $leafNodes
     */
    abstract public function getSubtotalDataRows(array $leafNodes): DefaultRows;



    /**
     * @return list<Block>
     */
    protected function getChildBlocks(): array
    {
        if ($this->childBlocks !== null) {
            return $this->childBlocks;
        }

        $childBlocks = [];

        foreach ($this->getChildren() as $childNode) {
            $childBlocks[] = $this->createBlock($childNode, $this->getLevel() + 1);
        }

        return $this->childBlocks = $childBlocks;
    }

    /**
     * @return list<Block>
     */
    protected function getBalancedChildBlocks(): array
    {
        if ($this->balancedChildBlocks !== null) {
            return $this->balancedChildBlocks;
        }

        $balancedChildBlocks = [];

        foreach ($this->getBalancedChildren() as $childNode) {
            $balancedChildBlocks[] = $this->createBlock($childNode, $this->getLevel() + 1);
        }

        return $this->balancedChildBlocks = $balancedChildBlocks;
    }

    protected function getOneChildBlock(): Block
    {
        $childBlock = $this->getChildBlocks()[0] ?? null;

        if ($childBlock === null) {
            throw new \RuntimeException('No child blocks found in the parent node.');
        }

        return $childBlock;
    }

    final protected function getParentNode(): BranchNode
    {
        return $this->parentNode;
    }

    /**
     * @return list<TreeNode>
     */
    final protected function getChildren(): array
    {
        if ($this->children !== null) {
            return $this->children;
        }

        /** @var \Traversable<array-key,TreeNode> */
        $children = $this->parentNode->getChildren();

        return $this->children = array_values(iterator_to_array($children));
    }

    /**
     * @return non-empty-list<TreeNode>
     */
    final protected function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        $children = $this->getChildren();

        /** @var non-empty-list<BranchNode> $children */
        return $this->balancedChildren = $this->balanceBranchNodes($children, $this->getLevel());
    }

    final protected function getOneChild(): TreeNode
    {
        return $this->getChildren()[0]
            ?? $this->getBalancedChildren()[0]
            ?? throw new \RuntimeException('No child nodes found in the parent node.');
    }
}
