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
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    private ?DefaultRows $headerRows = null;

    private ?DefaultRows $dataRows = null;

    public function __construct(
        BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($parentNode, $level, $context);
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->headerRows !== null) {
            return $this->headerRows;
        }

        $headerRows = new DefaultRows([], $this);

        // add a header and data column for each of the child blocks
        foreach ($this->getBalancedChildBlocks() as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        if (
            \count($this->getBalancedChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = new Subtotals($this->getParentNode());
            $subtotalHeaderRows = $this->getSubtotalHeaderRows($subtotals);
            $headerRows = $headerRows->appendRight($subtotalHeaderRows);
        }

        // add a legend if the dimension is not marked as superfluous
        $child = $this->getOneChild();

        if (!$this->getContext()->hasSuperfluousLegend($child)) {
            $nameCell = new DefaultHeaderCell(
                name: $child->getKey(),
                content: $child->getLegend(),
                generatingBlock: $this,
            );

            $headerRows = $nameCell->appendRowsBelow($headerRows);
        }

        return $this->headerRows = $headerRows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->dataRows !== null) {
            return $this->dataRows;
        }

        $dataRows = new DefaultRows([], $this);

        foreach ($this->getBalancedChildBlocks() as $childBlock) {
            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        if (
            \count($this->getBalancedChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = new Subtotals($this->getParentNode());
            $subtotalDataRows = $this->getSubtotalDataRows($subtotals, false);
            $dataRows = $dataRows->appendRight($subtotalDataRows);
        }

        return $this->dataRows = $dataRows;
    }

    #[\Override]
    public function getSubtotalHeaderRows(
        Subtotals $subtotals,
    ): DefaultRows {
        $headerRows = new DefaultRows([], $this);

        $headerRows = $headerRows
            ->appendRight($this->getOneChildBlock()->getSubtotalHeaderRows($subtotals));

        return $headerRows;
    }

    #[\Override]
    public function getSubtotalDataRows(
        Subtotals $subtotals,
        bool $requirePadding = true,
    ): DefaultRows {
        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%5D%2C%22columns%22%3A%5B%22%40values%22%2C%22itemCategory%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerCountry%22%3A%7B%22dimension%22%3A%22customerCountry%22%2C%22values%22%3A%5B%5D%7D%2C%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%5D%2C%22columns%22%3A%5B%22itemCategory%22%2C%22%40values%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerCountry%22%3A%7B%22dimension%22%3A%22customerCountry%22%2C%22values%22%3A%5B%5D%7D%2C%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%2C%22%40values%22%5D%2C%22columns%22%3A%5B%22itemCategory%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerCountry%22%3A%7B%22dimension%22%3A%22customerCountry%22%2C%22values%22%3A%5B%5D%7D%2C%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        $dataRows = new DefaultRows([], $this);
        $childBlock = $this->getOneBalancedChildBlock();

        if (!$childBlock instanceof NodeBlock) {
            throw new \RuntimeException(
                'The child block must be a NodeBlock to get subtotal rows.',
            );
        }

        if ($childBlock->getTreeNode()->getKey() === '@values') {
            foreach ($this->getBalancedChildBlocks() as $childBlock) {
                $childDataRows = $childBlock->getSubtotalDataRows($subtotals);
                $dataRows = $dataRows->appendRight($childDataRows);
            }
        } else {
            if ($requirePadding) {
                foreach ($this->getBalancedChildBlocks() as $childBlock) {
                    $childDataRows = $childBlock->getDataPaddingRows();
                    $dataRows = $dataRows->appendRight($childDataRows);
                }
            }

            $childDataRows = $childBlock->getSubtotalDataRows($subtotals);
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $dataRows;
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        $dataRows = new DefaultRows([], $this);

        foreach ($this->getBalancedChildBlocks() as $childBlock) {
            $childDataRows = $childBlock->getDataPaddingRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $dataRows;
    }
}
