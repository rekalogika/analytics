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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\TreeNode;

abstract class NodeBlock extends Block
{
    protected function __construct(
        private readonly TreeNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);
    }

    final protected function getTreeNode(): TreeNode
    {
        return $this->treeNode;
    }

    final protected function getBranchNode(): TreeNode
    {
        if ($this->treeNode->isLeaf()) {
            throw new \LogicException('Expected a branch node');
        }

        return $this->treeNode;
    }

    final protected function getLeafNode(): TreeNode
    {
        if (!$this->treeNode->isLeaf()) {
            throw new \LogicException('Expected a leaf node');
        }

        return $this->treeNode;
    }
}
