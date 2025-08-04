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

use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;

abstract class BranchBlock extends NodeBlock
{
    private BlockGroup $childrenBlockGroup;

    protected function __construct(
        TreeNodeDecorator $node,
        private ?TreeNodeDecorator $parentNode,
        ?Block $parent,
        BlockContext $context,
    ) {
        parent::__construct($node, $parent, $context);

        $this->childrenBlockGroup = $this->createBlockGroup($node);
    }

    final public function getChildrenBlockGroup(): BlockGroup
    {
        return $this->childrenBlockGroup;
    }

    /**
     * Blocks that contains blocks, each representing a child node
     */
    private function createBlockGroup(TreeNodeDecorator $node): BlockGroup
    {
        $children = $node->getChildren();
        $context = $this->getContext();

        $firstChild = $children[0]
            ?? $node->getBalancedChildren(1, $context->getBlockDepth())[0]
            ?? null;


        if ($firstChild === null) {
            return new EmptyBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                context: $context,
            );
        }

        if ($context->isPivoted($firstChild)) {
            return new HorizontalBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                context: $context,
            );
        } else {
            return new VerticalBlockGroup(
                node: $node,
                parentNode: $this->parentNode,
                context: $context,
            );
        }
    }
}
