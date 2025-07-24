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
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
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

        if (
            \count($this->getBalancedChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = iterator_to_array($this->getParentNode()->getSubtotals(), false);
            $subtotalDataRows = $this->getSubtotalDataRows($subtotals, false);

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
                    $childBlock->getSubtotalHeaderRow($leafNode),
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
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.',
        );
    }

    #[\Override]
    public function getSubtotalDataRows(
        iterable $leafNodes,
        bool $requirePadding = true,
    ): DefaultRows {
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
            $rows = new DefaultRows([], $this);
            $childSubtotalDataRows = $oneChildBlock->getSubtotalDataRows($leafNodes);

            if ($requirePadding) {
                foreach ($this->getChildBlocks() as $childBlock) {
                    $paddingRows = $childBlock->getDataPaddingRows();
                    $rows = $rows->appendRight($paddingRows);
                }
            }

            $rows = $rows->appendRight($childSubtotalDataRows);

            return $rows;
        }

        throw new \RuntimeException(
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.',
        );
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
