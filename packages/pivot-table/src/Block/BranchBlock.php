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

abstract class BranchBlock extends NodeBlock
{
    private BlockGroup $childrenBlockGroup;

    /**
     * @param int<0,max> $level
     */
    protected function __construct(
        TreeNode $node,
        private ?TreeNode $parentNode,
        ?Block $parent,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($node, $parent, $level, $context);
        $this->childrenBlockGroup = $this->createBlockGroup($node, $level);
    }

    final public function getChildrenBlockGroup(): BlockGroup
    {
        return $this->childrenBlockGroup;
    }

    /**
     * Blocks that contains blocks, each representing a child node
     *
     * @param int<0,max> $level
     */
    private function createBlockGroup(TreeNode $node, int $level): BlockGroup
    {

        /** @var \Traversable<array-key,TreeNode> */
        $children = $node->getChildren();
        $children = iterator_to_array($children);

        $firstChild = $children[0]
            ?? $this->getContext()->getDistinctNodesOfLevel($level)[0]
            ?? null;

        if ($firstChild === null) {
            return new EmptyBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                level: $level,
                context: $this->getContext(),
            );
        }

        if ($this->getContext()->isPivoted($firstChild)) {
            return new HorizontalBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                level: $level,
                context: $this->getContext(),
            );
        } else {
            return new VerticalBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                level: $level,
                context: $this->getContext(),
            );
        }
    }
}
