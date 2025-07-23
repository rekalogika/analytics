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

final class SingleNodeLeafBlock extends LeafBlock
{
    #[\Override]
    protected function getHeaderRows(): DefaultRows
    {
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    protected function getDataRows(): DefaultRows
    {
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getValue(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    protected function getSubtotalHeaderRows(iterable $leafNodes): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    #[\Override]
    protected function getSubtotalDataRows(array $leafNodes): DefaultRows
    {
        if (\count($leafNodes) !== 1) {
            throw new \LogicException('SingleNodeLeafBlock should only have one leaf node for subtotal rows.');
        }

        $leafNode = $leafNodes[0];

        $cell = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: $leafNode->getValue(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }
}
