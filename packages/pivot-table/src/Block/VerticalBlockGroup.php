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
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        return $this->getOneChildBlock()->getHeaderRows();
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $dataRows = new DefaultRows([], $this);

        foreach ($this->getChildBlocks() as $childBlock) {
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        if (\count($this->getChildBlocks()) === 1) {
            return $dataRows;
        }

        /**
         * @psalm-suppress InvalidArgument
         * @var list<LeafNode> $subtotals
         */
        $subtotals = iterator_to_array($this->getParentNode()->getSubtotals());
        $subtotalRows = $this->getSubtotalRows($subtotals);
        $dataRows = $dataRows->appendBelow($subtotalRows);

        return $dataRows;
    }

    #[\Override]
    protected function createSubtotalRows(iterable $leafNodes): DefaultRows
    {
        $rows = new DefaultRows([], $this);

        foreach ($leafNodes as $leafNode) {
            $rows = $rows->appendBelow($this->getOneChildBlock()->getSubtotalRows([$leafNode]));
        }

        return $rows;
    }
}
