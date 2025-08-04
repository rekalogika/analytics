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

use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    private ?DefaultRows $headerRows = null;

    private ?DefaultRows $dataRows = null;

    public function __construct(
        TreeNodeDecorator $node,
        ?TreeNodeDecorator $parentNode,
        BlockContext $context,
    ) {
        parent::__construct(
            node: $node,
            parentNode: $parentNode,
            context: $context,
        );
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->headerRows !== null) {
            return $this->headerRows;
        }

        $context = $this->getElementContext();
        $headerRows = new DefaultRows([], $context);

        // add a header and data column for each of the child blocks
        foreach ($this->getBalancedChildBlocksForHorizontalLayout() as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        // add a legend if the dimension is not marked as skipped
        $child = $this->getOneChild();

        if (!$this->getContext()->isLegendSkipped($child)) {
            $nameCell = new DefaultHeaderCell(
                name: $child->getKey(),
                content: $child->getLegend(),
                context: $context,
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

        $context = $this->getElementContext();
        $dataRows = new DefaultRows([], $context);

        foreach ($this->getBalancedChildBlocksForHorizontalLayout() as $childBlock) {
            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $this->dataRows = $dataRows;
    }

    /**
     * @param int<1,max> $level
     * @return list<Block>
     */
    private function getBalancedChildBlocksForHorizontalLayout(int $level = 1): array
    {
        $pivotedDimensions = $this->getContext()->getPivotedDimensions();
        $blocks = [];

        $children = $this->getNode()
            ->getBalancedChildrenFromNonPivotedParent($level, $pivotedDimensions);

        if (\count($children) > 1) {
            $subtotalNode = $this->getSubtotalNode($level);

            if ($subtotalNode !== null) {
                $children[] = $subtotalNode;
            }
        }

        foreach ($children as $childNode) {
            $blocks[] = $this->createBlock(
                node: $childNode,
                parentNode: $this->getNode(),
                levelIncrement: $level,
            );
        }

        return $blocks;
    }
}
