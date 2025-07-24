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

/**
 * @extends NodeBlock<BranchNode>
 */
abstract class BranchBlock extends NodeBlock
{
    private BlockGroup $childrenBlockGroup;

    protected function __construct(
        BranchNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, $level, $context);
        $this->childrenBlockGroup = $this->createBlockGroup($treeNode, $level);
    }

    final public function getChildrenBlockGroup(): BlockGroup
    {
        return $this->childrenBlockGroup;
    }

    /**
     * @param list<LeafNode> $leafNodes
     */
    abstract public function getSubtotalHeaderRows(array $leafNodes): DefaultRows;

    /**
     * @param list<LeafNode> $leafNodes
     */
    abstract public function getSubtotalDataRows(array $leafNodes): DefaultRows;

    private function createBlockGroup(BranchNode $parentNode, int $level): BlockGroup
    {

        /** @var \Traversable<array-key,TreeNode> */
        $children = $parentNode->getChildren();
        $children = iterator_to_array($children);

        $firstChild = $children[0]
            ?? $this->getContext()->getDistinctNodesOfLevel($level)[0]
            ?? null;

        if ($firstChild === null) {
            return new EmptyBlockGroup($parentNode, $level, $this->getContext());
        }

        if ($this->getContext()->isPivoted($firstChild)) {
            return new HorizontalBlockGroup($parentNode, $level, $this->getContext());
        } else {
            return new VerticalBlockGroup($parentNode, $level, $this->getContext());
        }
    }
}
