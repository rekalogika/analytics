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
use Rekalogika\PivotTable\Implementation\Table\DefaultCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
{
    private DefaultRows $headerRows;

    private DefaultRows $dataRows;

    public function __construct(
        BranchNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($parentNode, $level, $context);

        $dataRows = new DefaultRows([], $this);

        // add a data row for each of the child blocks
        foreach ($this->getChildBlocks() as $childBlock) {
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        // add subtotals if there are more than one child blocks
        if (
            \count($this->getChildBlocks()) > 1
            && $this->getOneChild()->getKey() !== '@values'
        ) {
            $subtotals = iterator_to_array($this->getParentNode()->getSubtotals(), false);
            $subtotalDataRows = $this->getSubtotalDataRows($subtotals);
            $dataRows = $dataRows->appendBelow($subtotalDataRows);

            // $dataRows = $dataRows->appendBelow($childBlock->getSubtotalDataRows($subtotals));

            // /**
            //  * @psalm-suppress InvalidArgument
            //  * @var list<LeafNode> $subtotals
            //  */
            // $subtotals = iterator_to_array($this->getParentNode()->getSubtotals());
            // $subtotalRows = [];

            // foreach ($this->getSubtotalDataRows($subtotals) as $subtotalRow) {
            //     $subtotalCells = iterator_to_array($subtotalRow, false);
            //     $first = array_shift($subtotalCells);

            //     if (!$first instanceof DefaultCell) {
            //         throw new \LogicException('Subtotal row must have at least one cell.');
            //     }

            //     $first = $first->withColumnSpan($dataRows->getWidth() - \count($subtotalCells));

            //     $subtotalRow = new DefaultRow(
            //         [$first, ...$subtotalCells],
            //         $this,
            //     );

            //     $subtotalRows[] = $subtotalRow;
            // }

            // $subtotalRows = new DefaultRows($subtotalRows, $this);
            // $dataRows = $dataRows->appendBelow($subtotalRows);
        }

        $this->headerRows = $this->getOneChildBlock()->getHeaderRows();
        $this->dataRows = $dataRows;
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
        throw new \BadMethodCallException('Not implemented yet');
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
                $rows = $rows->appendBelow($childSubtotalDataRows);
            }

            return $rows;
        } elseif (
            $oneChildBlock instanceof BlockGroup
            || $oneChildBlock instanceof BranchBlock
        ) {
            return $oneChildBlock->getSubtotalDataRows($leafNodes);
        }

        throw new \RuntimeException(
            'The child block must be a LeafBlock, BlockGroup, or BranchBlock to get subtotal rows.'
        );
    }
}
