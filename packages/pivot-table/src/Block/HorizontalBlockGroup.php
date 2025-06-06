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

use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        $rows = new DefaultRows([]);
        $children = $this->getBalancedChildren();

        foreach ($children as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $childRows = $childBlock->getHeaderRows();
            $rows = $rows->appendRight($childRows);
        }

        $firstChild = $children[0];

        if (
            !$this->getContext()->hasSuperfluousLegend($firstChild)
        ) {
            $nameCell = new DefaultHeaderCell(
                name: $firstChild->getKey(),
                content: $firstChild->getLegend(),
            );

            $rows = $nameCell->appendRowsBelow($rows);
        }

        return $rows;
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $rows = new DefaultRows([]);

        foreach ($this->getBalancedChildren() as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $childRows = $childBlock->getDataRows();
            $rows = $rows->appendRight($childRows);
        }

        return $rows;
    }
}
