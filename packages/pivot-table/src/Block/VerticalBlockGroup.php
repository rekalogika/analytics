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
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
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

        return $this->headerRows = $this->getOneChildBlock()->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->dataRows !== null) {
            return $this->dataRows;
        }

        $dataRows = new DefaultRows([], $this);

        // add a data row for each of the child blocks
        foreach ($this->getChildBlocks() as $childBlock) {
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        if (
            \count($this->getChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = new Subtotals($this->getParentNode());
            $subtotalDataRows = $this->getSubtotalDataRows($subtotals);
            $dataRows = $dataRows->appendBelow($subtotalDataRows);
        }

        return $this->dataRows = $dataRows;
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
        $dataRows = new DefaultRows([], $this);
        $childBlock = $this->getOneChildBlock();

        if (!$childBlock instanceof NodeBlock) {
            throw new \RuntimeException(
                'The child block must be a NodeBlock to get subtotal rows.',
            );
        }

        // $dataRows = $dataRows
        //     ->appendBelow($this->getOneChildBlock()->getSubtotalDataRows($subtotals));

        if ($childBlock->getTreeNode()->getKey() === '@values') {
            foreach ($this->getChildBlocks() as $childBlock) {
                $childDataRows = $childBlock->getSubtotalDataRows($subtotals);
                $dataRows = $dataRows->appendBelow($childDataRows);
            }
        } else {
            $childDataRows = $childBlock->getSubtotalDataRows($subtotals);
            $dataRows = $dataRows->appendBelow($childDataRows);
        }

        return $dataRows;


        // $dataRows = new DefaultRows([], $this);
        // $childBlock = $this->getOneChildBlock();

        // if ($childBlock instanceof LeafBlock) {
        //     // @todo consider http://127.0.0.1:8001/summary/page/d7aedf8d8f2812b74b5f0c02f35e3f07?parameters=%7B%22rows%22%3A%5B%22customerCountry%22%2C%22customerType%22%2C%22%40values%22%5D%2C%22columns%22%3A%5B%22itemCategory%22%5D%2C%22values%22%3A%5B%22price%22%2C%22count%22%5D%2C%22filterExpressions%22%3A%7B%22customerCountry%22%3A%7B%22dimension%22%3A%22customerCountry%22%2C%22values%22%3A%5B%5D%7D%2C%22customerType%22%3A%7B%22dimension%22%3A%22customerType%22%2C%22values%22%3A%5B%5D%7D%2C%22itemCategory%22%3A%7B%22dimension%22%3A%22itemCategory%22%2C%22values%22%3A%5B%5D%7D%7D%7D

        //     foreach ($leafNodes as $leafNode) {
        //         $childSubtotalDataRows = $childBlock
        //             ->getSubtotalDataRow($leafNode, $leafNodes);

        //         $dataRows = $dataRows->appendBelow($childSubtotalDataRows);
        //     }
        // } elseif (
        //     $childBlock instanceof BlockGroup
        //     || $childBlock instanceof BranchBlock
        // ) {
        //     $dataRows = $dataRows
        //         ->appendBelow($childBlock->getSubtotalDataRows($leafNodes));
        // } else {
        //     throw new \RuntimeException(
        //         'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.',
        //     );
        // }

        // return $dataRows;
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
