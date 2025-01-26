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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\Table\DataCell;
use Rekalogika\Analytics\PivotTable\Table\Rows;

class PivotBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        $valueCell = new DataCell($this->getBranchNode()->getItem());
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
