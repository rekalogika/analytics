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

/**
 * @extends NodeBlock<BranchNode>
 */
final class PivotBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        if (
            $this->getContext()->hasSuperfluousLegend($this->getTreeNode())
        ) {
            $valueCell = new DefaultHeaderCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getField(),
            );
        } else {
            $valueCell = new DefaultDataCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getField(),
            );
        }

        $blockGroup = $this->createGroupBlock($this->getTreeNode(), $this->getLevel());
        $rows = $blockGroup->getHeaderRows();

        $rows = $valueCell->appendRowsBelow($rows);

        return $rows;
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        return $this
            ->createGroupBlock($this->getTreeNode(), $this->getLevel())
            ->getDataRows();
    }
}
