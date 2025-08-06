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

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $headerRows = new DefaultRows([], $context);
        $prototypeNodes = $this->getPrototypeNodes();

        // add a header and data column for each of the child blocks
        foreach ($this->getChildBlocks($prototypeNodes) as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        // add a legend if the dimension is not marked as skipped
        $child = $this->getOneChildTreeNode($prototypeNodes);

        if (!$this->getContext()->isLegendSkipped($child->getKey())) {
            $nameCell = new DefaultHeaderCell(
                name: $child->getKey(),
                content: $child->getLegend(),
                context: $context,
            );

            $headerRows = $nameCell->appendRowsBelow($headerRows);
        }

        return $headerRows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $dataRows = new DefaultRows([], $context);
        $prototypeNodes = $this->getPrototypeNodes();

        foreach ($this->getChildBlocks($prototypeNodes) as $childBlock) {
            $childDataRows = $childBlock->getDataRows();
            $dataRows = $dataRows->appendRight($childDataRows);
        }

        return $dataRows;
    }

    /**
     * @return non-empty-list<TreeNode>
     */
    private function getPrototypeNodes(): array
    {
        $result = $this->getContext()
            ->getRootTreeNode()
            ->drillDown($this->getChildKey());

        $result = iterator_to_array($result, false);

        if ($result === []) {
            throw new \RuntimeException(\sprintf(
                'No prototype nodes found for child key "%s".',
                $this->getChildKey(),
            ));
        }

        return $result;
    }

    // /**
    //  * @param int<1,max> $level
    //  * @return list<Block>
    //  */
    // private function getBalancedChildBlocksForHorizontalLayout(int $level = 1): array
    // {
    //     $pivotedNodes = $this->getContext()->getPivotedKeys();
    //     $blocks = [];

    //     $children = $this->getNode()
    //         ->getBalancedChildrenFromNonPivotedParent($level, $pivotedNodes);

    //     if (\count($children) > 1) {
    //         $subtotalNode = $this->getSubtotalNode($level);

    //         if ($subtotalNode !== null) {
    //             $children[] = $subtotalNode;
    //         }
    //     }

    //     foreach ($children as $childNode) {
    //         $blocks[] = $this->createBlock(
    //             node: $childNode,
    //             levelIncrement: $level,
    //             key: $childNode->getKey(),
    //         );
    //     }

    //     return $blocks;
    // }
}
