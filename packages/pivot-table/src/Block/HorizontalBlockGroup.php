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
        $childBlock = $this->getOneChildBlock();

        if ($childBlock instanceof LeafBlock) {
            $rows = new DefaultRows([], $this);

            foreach ($leafNodes as $leafNode) {
                $rows = $rows->appendRight(
                    $childBlock->getSubtotalDataRow($leafNode)
                );
            }

            return $rows;
        } elseif (
            $childBlock instanceof BlockGroup
            || $childBlock instanceof BranchBlock
        ) {
            return $childBlock->getSubtotalDataRows($leafNodes);
        }


        throw new \RuntimeException(
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.'
        );
    }
}
