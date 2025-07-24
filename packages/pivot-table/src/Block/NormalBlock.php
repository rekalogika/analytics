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
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultFooterCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class NormalBlock extends BranchBlock
{
    private DefaultRows $headerRows;
    private DefaultRows $dataRows;

    protected function __construct(
        BranchNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, $level, $context);

        // Create header rows
        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            generatingBlock: $this,
        );

        $this->headerRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getHeaderRows());

        // Create data rows
        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        $this->dataRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getDataRows());
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
        $cell = new DefaultHeaderCell(
            name: 'Total',
            content: 'Total',
            generatingBlock: $this,
            columnSpan: $this->getHeaderRows()->getHeight(),
        );

        return DefaultRows::createFromCell($cell, $this);
    }

    /**
     * @todo make 'Total' string configurable
     */
    #[\Override]
    public function getSubtotalDataRows(array $leafNodes): DefaultRows
    {
        $cell = new DefaultFooterCell(
            name: '',
            content: 'Total',
            generatingBlock: $this,
        );

        $childredSubtotalRows = $this->getChildrenBlockGroup()->getSubtotalDataRows($leafNodes);

        return $cell->appendRowsRight($childredSubtotalRows);
    }
}
