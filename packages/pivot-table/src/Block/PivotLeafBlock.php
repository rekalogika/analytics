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
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class PivotLeafBlock extends LeafBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        if (
            $this->getContext()->hasSuperfluousLegend($this->getTreeNode())
        ) {
            $cell = new DefaultHeaderCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getItem(),
                generatingBlock: $this,
            );
        } else {
            $cell = new DefaultDataCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getItem(),
                generatingBlock: $this,
            );
        }

        $row = new DefaultRow([$cell], $this);

        return new DefaultRows([$row], $this);
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getValue(),
            generatingBlock: $this,
        );

        $row = new DefaultRow([$cell], $this);

        return new DefaultRows([$row], $this);
    }

    #[\Override]
    protected function createSubtotalRows(array $leafNodes): DefaultRows
    {
        if (\count($leafNodes) !== 1) {
            throw new \LogicException('PivotLeafBlock should only have one leaf node for subtotal rows.');
        }

        $leafNode = $leafNodes[0];

        $cell = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: $leafNode->getValue(),
            generatingBlock: $this,
        );

        $row = new DefaultRow([$cell], $this);

        return new DefaultRows([$row], $this);
    }
}
