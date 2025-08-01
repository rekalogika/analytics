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

use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class HorizontalBlockGroup extends BlockGroup
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

        $headerRows = new DefaultRows([], $this);

        // add a header and data column for each of the child blocks
        foreach ($this->getBalancedChildBlocks() as $childBlock) {
            $childHeaderRows = $childBlock->getHeaderRows();
            $headerRows = $headerRows->appendRight($childHeaderRows);
        }

        // add a legend if the dimension is not marked as skipped
        $child = $this->getOneChild();

        if (!$this->getContext()->isLegendSkipped($child)) {
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

        return $this->dataRows = $dataRows;
    }
}
