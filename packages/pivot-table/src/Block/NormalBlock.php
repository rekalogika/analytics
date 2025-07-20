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

use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class NormalBlock extends BranchBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            generatingBlock: $this,
        );

        return $cell->appendRowsRight($this->getChildrenBlockGroup()->getHeaderRows());
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        return $cell->appendRowsRight($this->getChildrenBlockGroup()->getDataRows());
    }

    /**
     * @todo make 'All' string configurable
     */
    #[\Override]
    protected function createSubtotalRows(array $leafNodes): DefaultRows
    {
        $cell = new DefaultFooterCell(
            name: '',
            content: 'All',
            generatingBlock: $this,
        );

        $childredSubtotalRows = $this->getChildrenBlockGroup()->getSubtotalRows($leafNodes);

        return $cell->appendRowsRight($childredSubtotalRows);
    }
}
