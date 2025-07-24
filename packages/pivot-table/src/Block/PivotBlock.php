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
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class PivotBlock extends BranchBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if (
            $this->getContext()->hasSuperfluousLegend($this->getTreeNode())
        ) {
            $valueCell = new DefaultHeaderCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getItem(),
                generatingBlock: $this,
            );
        } else {
            $valueCell = new DefaultDataCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getItem(),
                generatingBlock: $this,
            );
        }

        $rows = $this->getChildrenBlockGroup()->getHeaderRows();
        $rows = $valueCell->appendRowsBelow($rows);

        return $rows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }

    #[\Override]
    public function getSubtotalHeaderRows(iterable $leafNodes): DefaultRows
    {
        if ($this->getTreeNode() instanceof BranchNode) {
            return $this->getChildrenBlockGroup()->getSubtotalHeaderRows($leafNodes);
        }

        $valueCell = new DefaultHeaderCell(
            name: 'Total',
            content: 'Total',
            generatingBlock: $this,
            columnSpan: $this->getSubtotalDataRows($leafNodes)->getWidth(),
            rowSpan: $this->getSubtotalDataRows($leafNodes)->getHeight(),
        );

        return DefaultRows::createFromCell($valueCell, $this);
    }

    #[\Override]
    public function getSubtotalDataRows(array $leafNodes): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getSubtotalDataRows($leafNodes);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataPaddingRows();
    }
}
