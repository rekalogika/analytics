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

use Rekalogika\PivotTable\Block\Util\Subtotals;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class RootBlock extends BranchBlock
{
    protected function __construct(
        TreeNode $treeNode,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, null, 0, $context);
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        return $this
            ->getChildrenBlockGroup()
            ->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }

    #[\Override]
    public function getSubtotalHeaderRows(
        Subtotals $subtotals,
    ): DefaultRows {
        throw new \BadMethodCallException('Not implemented yet');
    }

    #[\Override]
    public function getSubtotalDataRows(
        Subtotals $subtotals,
    ): DefaultRows {
        return $this->getChildrenBlockGroup()->getSubtotalDataRows($subtotals);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
