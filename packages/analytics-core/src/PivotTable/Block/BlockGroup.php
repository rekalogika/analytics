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
use Rekalogika\Analytics\PivotTable\TreeNode;

abstract class BlockGroup extends Block
{
    final protected function __construct(
        private readonly BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);
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
        return $this->parentNode->getChildren();
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
