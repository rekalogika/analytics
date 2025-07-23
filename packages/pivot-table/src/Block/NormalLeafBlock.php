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

final class NormalLeafBlock extends LeafBlock
{
    #[\Override]
    protected function getHeaderRows(): DefaultRows
    {
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            columnSpan: 2,
            generatingBlock: $this,
        );

        $row = new DefaultRow([$cell], $this);

        return new DefaultRows([$row], $this);
    }

    #[\Override]
    protected function getDataRows(): DefaultRows
    {
        $name = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        $value = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getValue(),
            generatingBlock: $this,
        );

        $row = new DefaultRow([$name, $value], $this);

        return new DefaultRows([$row], $this);
    }

    #[\Override]
    protected function getSubtotalDataRows(array $leafNodes): DefaultRows
    {
        if (\count($leafNodes) !== 1) {
            throw new \LogicException('NormalLeafBlock should only have one leaf node for subtotal rows.');
        }

        $leafNode = $leafNodes[0];

        $name = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: 'Total',
            generatingBlock: $this,
        );

        $value = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: $leafNode->getValue(),
            generatingBlock: $this,
        );

        $row = new DefaultRow([$name, $value], $this);

        return new DefaultRows([$row], $this);
    }
}
