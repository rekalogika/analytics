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
use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class PivotLeafBlock extends LeafBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
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
        $leafNode = $subtotals->takeOne();

        $cell = new DefaultFooterHeaderCell(
            name: 'total',
            content: 'Total',
            generatingBlock: $this,
        );

        $rows = DefaultRows::createFromCell($cell, $this);

        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerType%22%5D%2C%22columns%22%3A%5B%22itemCategory%22%2C%22%40values%22%5D%2C%22values%22%3A%5B%22count%22%2C%22price%22%2C%22priceRange%22%5D%2C%22filterExpressions%22%3A%7B%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        if (
            $leafNode->getKey() === '@values'
            // && !$leafNode instanceof SubtotalNode
        ) {
            $rows = $rows->appendBelow(
                DefaultRows::createFromCell(
                    new DefaultHeaderCell(
                        name: $leafNode->getKey(),
                        content: $leafNode->getItem(),
                        generatingBlock: $this,
                    ),
                    $this,
                ),
            );
        }

        return $rows;
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
        $cell = new DefaultFooterCell(
            name: '',
            content: '',
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }
}
