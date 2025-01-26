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

use Rekalogika\Analytics\PivotTable\BranchNode;
use Rekalogika\Analytics\PivotTable\LeafNode;
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

    final protected function getBranchNode(): BranchNode
    {
        if (!$this->treeNode instanceof BranchNode) {
            throw new \LogicException('Expected a BranchNode');
        }

        return $this->treeNode;
    }

    final protected function getLeafNode(): LeafNode
    {
        if (!$this->treeNode instanceof LeafNode) {
            throw new \LogicException('Expected a LeafNode');
        }

        return $this->treeNode;
    }
}
