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

use Rekalogika\PivotTable\Block\Util\Subtotals;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class SingleNodeLeafBlock extends LeafBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getValue(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    public function getSubtotalHeaderRows(
        Subtotals $subtotals,
    ): DefaultRows {
        throw new \BadMethodCallException('Not implemented yet');
    }

    #[\Override]
    public function getSubtotalDataRows(
        Subtotals $subtotals,
    ): DefaultRows {
        $leafNode = $subtotals->takeOne();

        $cell = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: $leafNode->getValue(),
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
