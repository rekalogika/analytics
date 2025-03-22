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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\Table\ContentType;
use Rekalogika\Analytics\PivotTable\Table\HeaderCell;
use Rekalogika\Analytics\PivotTable\Table\Rows;

final class HorizontalBlockGroup extends BlockGroup
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        $rows = new Rows([]);
        $children = $this->getBalancedChildren();

        foreach ($children as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $childRows = $childBlock->getHeaderRows();
            $rows = $rows->appendRight($childRows);
        }

        $firstChild = $children[0];

        if (!$this->getContext()->hasSuperfluousLegend($firstChild)) {
            $nameCell = new HeaderCell(
                type: ContentType::Legend,
                key: $firstChild->getKey(),
                content: $firstChild->getLegend(),
                treeNode: $firstChild,
            );

            $rows = $nameCell->appendRowsBelow($rows);
        }

        return $rows;
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        $rows = new Rows([]);

        foreach ($this->getBalancedChildren() as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $childRows = $childBlock->getDataRows();
            $rows = $rows->appendRight($childRows);
        }

        return $rows;
    }
}
