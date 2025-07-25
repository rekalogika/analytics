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
    private ?DefaultRows $headerRows = null;
    private ?DefaultRows $dataRows = null;

    protected function __construct(
        BranchNode $treeNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($treeNode, $level, $context);
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->headerRows !== null) {
            return $this->headerRows;
        }

        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            generatingBlock: $this,
        );

        return $this->headerRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getHeaderRows());
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->dataRows !== null) {
            return $this->dataRows;
        }

        $cell = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            generatingBlock: $this,
        );

        return $this->dataRows = $cell
            ->appendRowsRight($this->getChildrenBlockGroup()->getDataRows());
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

        $subtotalHeaderRows = $this->getChildrenBlockGroup()
            ->getSubtotalHeaderRows($leafNodes);

        return $cell->appendRowsRight($subtotalHeaderRows);
    }

    #[\Override]
    public function getSubtotalDataRows(array $leafNodes): DefaultRows
    {
        // if ($this->getTreeNode()->getKey() === '@values') {
        //     $cell = new DefaultFooterCell(
        //         name: $this->getTreeNode()->getKey(),
        //         content: $this->getTreeNode()->getItem(),
        //         generatingBlock: $this,
        //     );
        // } else {
            $cell = new DefaultFooterCell(
                name: '',
                content: 'Total',
                generatingBlock: $this,
            );
        // }

        $childredSubtotalRows = $this->getChildrenBlockGroup()
            ->getSubtotalDataRows($leafNodes);

        return $cell->appendRowsRight($childredSubtotalRows);
    }

    #[\Override]
    public function getDataPaddingRows(): DefaultRows
    {
        throw new \BadMethodCallException('Not implemented yet');
    }
}
