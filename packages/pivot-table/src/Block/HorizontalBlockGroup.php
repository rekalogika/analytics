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

use Rekalogika\PivotTable\Contracts\Tree\BranchNode;
use Rekalogika\PivotTable\Contracts\Tree\LeafNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    private DefaultRows $headerRows;

    private DefaultRows $dataRows;

    public function __construct(
        BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($parentNode, $level, $context);

        $headerRows = new DefaultRows([], $this);
        $dataRows = new DefaultRows([], $this);

        // add a header and data column for each of the child blocks
        foreach ($this->getBalancedChildBlocks() as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);

            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        // add subtotals if there are more than one child blocks
        if (
            \count($this->getChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = iterator_to_array($this->getParentNode()->getSubtotals(), false);
            $subtotalDataRows = $this->getSubtotalDataRows($subtotals);

            // $subtotalHeaderRows = $this->getOneChildBlock()
            //     ->getSubtotalHeaderRows($subtotals);

            $subtotalHeaderRows = $this->getSubtotalHeaderRows($subtotals);
            $headerRows = $headerRows->appendRight($subtotalHeaderRows);
            $dataRows = $dataRows->appendRight($subtotalDataRows);
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

        $this->dataRows = $dataRows;
        $this->headerRows = $headerRows;
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        return $this->headerRows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->dataRows;
    }

    #[\Override]
    public function getSubtotalHeaderRows(iterable $leafNodes): DefaultRows
    {
        $childBlock = $this->getOneChildBlock();

        if ($childBlock instanceof LeafBlock) {
            $rows = new DefaultRows([], $this);

            foreach ($leafNodes as $leafNode) {
                $rows = $rows->appendRight(
                    $childBlock->getSubtotalHeaderRow($leafNode)
                );
            }

            return $rows;
        } elseif (
            $childBlock instanceof BlockGroup
            || $childBlock instanceof BranchBlock
        ) {
            return $childBlock->getSubtotalHeaderRows($leafNodes);
        }

        throw new \RuntimeException(
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.'
        );
    }

    #[\Override]
    public function getSubtotalDataRows(iterable $leafNodes): DefaultRows
    {
        $oneChildBlock = $this->getOneChildBlock();

        if ($oneChildBlock instanceof LeafBlock) {
            $rows = new DefaultRows([], $this);

            foreach ($this->getChildBlocks() as $childBlock) {
                $leafNode = array_shift($leafNodes);

                if (!$leafNode instanceof LeafNode) {
                    continue;
                }

                $childSubtotalDataRows = $childBlock->getSubtotalDataRow($leafNode);
                $rows = $rows->appendRight($childSubtotalDataRows);
            }

            // foreach ($leafNodes as $leafNode) {
            //     $rows = $rows->appendRight(
            //         $oneChildBlock->getSubtotalDataRow($leafNode)
            //     );
            // }

            return $rows;
        } elseif (
            $oneChildBlock instanceof BlockGroup
            || $oneChildBlock instanceof BranchBlock
        ) {
            return $oneChildBlock->getSubtotalDataRows($leafNodes);


            // $rows = new DefaultRows([], $this);
            // $row = new DefaultRow([], $this);

            // $childSubtotalDataRows = iterator_to_array($oneChildBlock->getSubtotalDataRows($leafNodes), false);

            // $subtotalDataRow = array_shift($childSubtotalDataRows);

            // if ($subtotalDataRow === null) {
            //     return $rows;
            // }

            // $subtotalDataRow = \iterator_to_array($subtotalDataRow, false);

            // foreach ($this->getChildBlocks() as $childBlock) {
            //     $childDataRows = $childBlock->getDataRows();

            //     foreach ($childDataRows as $childDataRow) {
            //         $subtotalDataCell = array_shift($subtotalDataRow);

            //         if ($subtotalDataCell === null) {
            //             continue;
            //         }

            //         foreach ($childDataRow as $childDataCell) {
            //             if (!$childDataCell instanceof DefaultDataCell) {
            //                 continue;
            //             }

            //             $emptyCell = new DefaultFooterCell(
            //                 name: '',
            //                 content: '',
            //                 generatingBlock: $this,
            //             );

            //             $row = $row->appendCell($emptyCell);
            //         }

            //         $row = $row->appendCell($subtotalDataCell);
            //     }
            // }

            // $rows = $rows->appendRow($row);

            // return $rows;
        }

        throw new \RuntimeException(
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.'
        );
    }
}
