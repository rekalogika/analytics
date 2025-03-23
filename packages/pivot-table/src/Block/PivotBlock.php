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

final class PivotBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        if (
            $this->getContext()->hasSuperfluousLegend($this->getBranchNode())
        ) {
            $valueCell = new HeaderCell(
                type: ContentType::Item,
                key: $this->getBranchNode()->getKey(),
                content: $this->getBranchNode()->getItem(),
                treeNode: $this->getBranchNode(),
            );
        } else {
            $valueCell = new DataCell(
                type: ContentType::Item,
                key: $this->getBranchNode()->getKey(),
                content: $this->getBranchNode()->getItem(),
                treeNode: $this->getBranchNode(),
            );
        }

        $blockGroup = $this->createGroupBlock($this->getBranchNode(), $this->getLevel());
        $rows = $blockGroup->getHeaderRows();

        $rows = $valueCell->appendRowsBelow($rows);

        return $rows;
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getDataRows();
    }
}
