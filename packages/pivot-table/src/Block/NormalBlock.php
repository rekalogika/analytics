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
        );

        return $cell->appendRowsRight($this->getChildrenBlockGroup()->getHeaderRows());
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
        );

        return $cell->appendRowsRight($this->getChildrenBlockGroup()->getDataRows());
    }
}
