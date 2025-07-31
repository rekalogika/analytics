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

use Rekalogika\PivotTable\Contracts\Tree\TreeNode;

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
        private readonly TreeNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);
    }

    /**
     * @return list<Block>
     */
    public function getChildBlocks(): array
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
    public function getBalancedChildBlocks(): array
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

    public function getOneChildBlock(): Block
    {
        return $this->getChildBlocks()[0]
            ?? throw new \RuntimeException('No child blocks found in the parent node.');
    }

    public function getOneBalancedChildBlock(): Block
    {
        return $this->getBalancedChildBlocks()[0]
            ?? throw new \RuntimeException('No child blocks found in the parent node.');
    }

    final public function getParentNode(): TreeNode
    {
        return $this->parentNode;
    }

    /**
     * @return list<TreeNode>
     */
    final public function getChildren(): array
    {
        if ($this->children !== null) {
            return $this->children;
        }

        $children = $this->parentNode->getChildren();

        return $this->children = array_values(iterator_to_array($children));
    }

    /**
     * @return non-empty-list<TreeNode>
     */
    final public function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        $children = $this->getChildren();

        /** @var non-empty-list<TreeNode> $children */
        return $this->balancedChildren = $this->balanceBranchNodes($children, $this->getLevel());
    }

    final public function getOneChild(): TreeNode
    {
        return $this->getChildren()[0]
            ?? $this->getBalancedChildren()[0]
            ?? throw new \RuntimeException('No child nodes found in the parent node.');
    }
}
