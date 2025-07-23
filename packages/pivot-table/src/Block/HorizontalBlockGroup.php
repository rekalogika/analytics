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
        if (\count($this->getBalancedChildren()) > 1) {
            /**
             * @psalm-suppress InvalidArgument
             * @var list<LeafNode> $subtotals
             */
            $subtotals = iterator_to_array($this->getParentNode()->getSubtotals());
            $subtotalRows = $this->getSubtotalRows($subtotals);


            $subtotalHeaderCell = new DefaultHeaderCell(
                name: 'Total',
                content: 'Total',
                generatingBlock: $this,
                rowSpan: $headerRows->getHeight(),
            );
            $subtotalHeaderRow = new DefaultRow([$subtotalHeaderCell], $this);
            $subtotalHeaderRows = new DefaultRows([$subtotalHeaderRow], $this);

            // foreach ($children as $childNode) {
            //     $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);

            //     $childHeaderRows = $childBlock->getHeaderRows();
            //     $headerRows = $headerRows->appendRight($childHeaderRows);

            //     $childDataRows = $childBlock->getDataRows();
            //     $dataRows = $dataRows->appendRight($childDataRows);
            // }


            $subtotalHeaderRows = $this->getOneChildBlock()->getHeaderRows();


            $headerRows = $headerRows->appendRight($subtotalHeaderRows);
            $dataRows = $dataRows->appendRight($subtotalRows);
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
    protected function getHeaderRows(): DefaultRows
    {
        return $this->headerRows;
    }

    #[\Override]
    protected function getDataRows(): DefaultRows
    {
        return $this->dataRows;
    }

    #[\Override]
    protected function getSubtotalRows(iterable $leafNodes): DefaultRows
    {
        $rows = new DefaultRows([], $this);

        foreach ($leafNodes as $leafNode) {
            $rows = $rows->appendRight($this->getOneChildBlock()->getSubtotalRows([$leafNode]));
        }

        return $rows;
    }

    private function getOneChild(): TreeNode
    {
        return $this->getBalancedChildren()[0]
            ?? throw new \RuntimeException('No child nodes found in the parent node.');
    }
}
