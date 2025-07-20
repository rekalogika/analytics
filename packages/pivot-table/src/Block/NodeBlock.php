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

use Rekalogika\PivotTable\Contracts\Tree\LeafNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

/**
 * @template T of TreeNode
 */
abstract class NodeBlock extends Block
{
    private ?DefaultRows $headerRowsCache = null;

    private ?DefaultRows $dataRowsCache = null;

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

    #[\Override]
    final protected function getHeaderRows(): DefaultRows
    {
        return $this->headerRowsCache ??= $this->createHeaderRows();
    }

    #[\Override]
    final protected function getDataRows(): DefaultRows
    {
        return $this->dataRowsCache ??= $this->createDataRows();
    }

    /**
     * @param list<LeafNode> $leafNodes
     */
    #[\Override]
    final protected function getSubtotalRows(array $leafNodes): DefaultRows
    {
        return $this->createSubtotalRows($leafNodes);
    }

    /**
     * @param list<LeafNode> $leafNodes
     */
    abstract protected function createSubtotalRows(array $leafNodes): DefaultRows;

    abstract protected function createHeaderRows(): DefaultRows;

    abstract protected function createDataRows(): DefaultRows;
}
