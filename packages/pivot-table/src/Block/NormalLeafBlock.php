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
use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class NormalLeafBlock extends LeafBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            columnSpan: 2,
            generatingBlock: $this,
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    #[\Override]
    public function getDataRows(): DefaultRows
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
    public function getSubtotalHeaderRow(LeafNode $leafNode): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    #[\Override]
    public function getSubtotalDataRow(
        LeafNode $leafNode,
        array $allLeafNodes
    ): DefaultRows {
        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerType%22%2C%22%40values%22%2C%22itemCategory%22%5D%2C%22values%22%3A%5B%22count%22%2C%22price%22%2C%22priceRange%22%5D%2C%22filterExpressions%22%3A%7B%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        if (
            $this->getTreeNode()->getKey() === '@values'
            || count($allLeafNodes) > 1
        ) {
            $name = new DefaultFooterCell(
                name: $leafNode->getKey(),
                content: $leafNode->getItem(),
                generatingBlock: $this,
            );
        } else {
            $name = new DefaultFooterCell(
                name: '',
                content: 'Total',
                generatingBlock: $this,
            );
        }

        $value = new DefaultFooterCell(
            name: $leafNode->getKey(),
            content: $leafNode->getValue(),
            generatingBlock: $this,
        );

        $row = new DefaultRow([$name, $value], $this);

        return new DefaultRows([$row], $this);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
