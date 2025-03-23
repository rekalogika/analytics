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

use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\Table\ContentType;

final class PivotBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        if (
            $this->getContext()->hasSuperfluousLegend($this->getBranchNode())
        ) {
            $valueCell = new DefaultHeaderCell(
                type: ContentType::Item,
                key: $this->getBranchNode()->getKey(),
                content: $this->getBranchNode()->getItem(),
                treeNode: $this->getBranchNode(),
            );
        } else {
            $valueCell = new DefaultDataCell(
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
    protected function createDataRows(): DefaultRows
    {
        return $this
            ->createGroupBlock($this->getBranchNode(), $this->getLevel())
            ->getDataRows();
    }
}
