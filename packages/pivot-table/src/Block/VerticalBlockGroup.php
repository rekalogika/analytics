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
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
{
    private ?DefaultRows $headerRows = null;

    private ?DefaultRows $dataRows = null;

    public function __construct(
        TreeNode $node,
        ?TreeNode $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct(
            node: $node,
            parentNode: $parentNode,
            level: $level,
            context: $context,
        );
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
            $subtotals = new Subtotals($this->getNode());
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
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
