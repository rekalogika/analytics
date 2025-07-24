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
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

/**
 * @template T of TreeNode
 */
abstract class NodeBlock extends Block
{
    /**
     * @param T $treeNode
     */
    protected function __construct(
        private readonly TreeNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);
    }

    /**
     * @return T
     */
    final protected function getTreeNode(): TreeNode
    {
        return $this->treeNode;
    }
}
