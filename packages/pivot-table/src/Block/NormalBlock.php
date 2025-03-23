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

use Rekalogika\PivotTable\Table\ContentType;
use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\HeaderCell;
use Rekalogika\PivotTable\Table\Rows;

final class NormalBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        $cell = new HeaderCell(
            type: ContentType::Legend,
            key: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            treeNode: $this->getTreeNode(),
        );

        $blockGroup = $this->createGroupBlock($this->getBranchNode(), $this->getLevel());

        return $cell->appendRowsRight($blockGroup->getHeaderRows());
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        $cell = new DataCell(
            type: ContentType::Item,
            key: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            treeNode: $this->getTreeNode(),
        );

        $blockGroup = $this->createGroupBlock($this->getBranchNode(), $this->getLevel());

        return $cell->appendRowsRight($blockGroup->getDataRows());
    }
}
