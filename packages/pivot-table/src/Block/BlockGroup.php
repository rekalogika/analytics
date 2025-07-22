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
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;

abstract class BlockGroup extends Block
{
    /**
     * @var list<Block>
     */
    private readonly array $childBlocks;

    public function __construct(
        private readonly BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);

        $childBlocks = [];

        foreach ($this->getChildren() as $childNode) {
            $childBlocks[] = $this->createBlock($childNode, $this->getLevel() + 1);
        }

        $this->childBlocks = $childBlocks;
    }

    /**
     * @return list<Block>
     */
    protected function getChildBlocks(): array
    {
        return $this->childBlocks;
    }

    protected function getOneChild(): TreeNode
    {
        $children = $this->getChildren();

        return $children[0] ?? throw new \RuntimeException('No child nodes found in the parent node.');
    }

    protected function getOneChildBlock(): Block
    {
        $childBlock = $this->childBlocks[0] ?? null;

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
        /** @var \Traversable<array-key,TreeNode> */
        $children = $this->parentNode->getChildren();

        return array_values(iterator_to_array($children));
    }

    /**
     * @return non-empty-list<TreeNode>
     */
    final protected function getBalancedChildren(): array
    {
        $children = $this->getChildren();

        /** @var non-empty-list<BranchNode> $children */
        return $this->balanceBranchNodes($children, $this->getLevel());
    }
}
