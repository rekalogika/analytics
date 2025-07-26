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
use Rekalogika\PivotTable\Contracts\Tree\BranchNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class NormalBlock extends BranchBlock
{
    private ?DefaultRows $headerRows = null;
    private ?DefaultRows $dataRows = null;

    protected function __construct(
        BranchNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, $level, $context);
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->headerRows !== null) {
            return $this->headerRows;
        }

        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            generatingBlock: $this,
        );

        return $this->headerRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getHeaderRows());
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->dataRows !== null) {
            return $this->dataRows;
        }

        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        return $this->dataRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getDataRows());
    }

    #[\Override]
    public function getSubtotalHeaderRows(
        Subtotals $subtotals,
    ): DefaultRows {
        $cell = new DefaultHeaderCell(
            name: 'Total',
            content: 'Total',
            generatingBlock: $this,
            columnSpan: $this->getHeaderRows()->getHeight(),
        );

        $subtotalHeaderRows = $this->getChildrenBlockGroup()
            ->getSubtotalHeaderRows($subtotals);

        return $cell->appendRowsRight($subtotalHeaderRows);
    }

    #[\Override]
    public function getSubtotalDataRows(
        Subtotals $subtotals,
    ): DefaultRows {
        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%2C%22%40values%22%5D%2C%22columns%22%3A%5B%22itemCategory%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerCountry%22%3A%7B%22dimension%22%3A%22customerCountry%22%2C%22values%22%3A%5B%5D%7D%2C%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerType%22%2C%22%40values%22%2C%22itemCategory%22%5D%2C%22values%22%3A%5B%22count%22%2C%22price%22%2C%22priceRange%22%5D%2C%22filterExpressions%22%3A%7B%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        if ($this->getTreeNode()->getKey() === '@values') {
            $cell = new DefaultFooterCell(
                name: $this->getTreeNode()->getKey(),
                content: $this->getTreeNode()->getItem(),
                generatingBlock: $this,
            );
        } else {
            $cell = new DefaultFooterHeaderCell(
                name: '',
                content: 'Total',
                generatingBlock: $this,
            );
        }

        $childredSubtotalRows = $this->getChildrenBlockGroup()
            ->getSubtotalDataRows($subtotals);

        return $cell->appendRowsRight($childredSubtotalRows);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
