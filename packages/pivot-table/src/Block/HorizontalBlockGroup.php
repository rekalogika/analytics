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
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        $rows = new DefaultRows([], $this);
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
                generatingBlock: $this,
            );

            $rows = $nameCell->appendRowsBelow($rows);
        }

        return $rows;
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $rows = new DefaultRows([], $this);

        foreach ($this->getBalancedChildren() as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $childRows = $childBlock->getDataRows();
            $rows = $rows->appendRight($childRows);
        }

        if (\count($this->getBalancedChildren()) === 1) {
            // if there is only one child, we don't need to add a subtotal
            return $rows;
        }

        /**
         * @psalm-suppress InvalidArgument
         * @var list<LeafNode> $subtotals
         */
        $subtotals = iterator_to_array($this->getParentNode()->getSubtotals());
        $subtotalRows = $this->getSubtotalRows($subtotals);
        $rows = $rows->appendRight($subtotalRows);

        return $rows;
    }

    #[\Override]
    protected function createSubtotalRows(iterable $leafNodes): DefaultRows
    {
        $rows = new DefaultRows([], $this);

        foreach ($leafNodes as $leafNode) {
            $rows = $rows->appendRight($this->getOneChildBlock()->getSubtotalRows([$leafNode]));
        }

        return $rows;
    }
}
