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

final class PivotBlock extends BranchBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $currentKey = $this->getContext()->getCurrentKey();

        if (
            $this->getContext()->isLegendSkipped($currentKey)
        ) {
            $valueCell = new DefaultHeaderCell(
                name: $currentKey,
                content: $this->getCube()->getMember($currentKey),
                context: $context,
            );
        } else {
            $valueCell = new DefaultDataCell(
                name: $currentKey,
                content: $this->getCube()->getMember($currentKey),
                context: $context,
            );
        }

        $rows = $this->getChildrenBlockGroup()->getHeaderRows();
        $rows = $valueCell->appendRowsBelow($rows);

        return $rows;
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        return $this->getChildrenBlockGroup()->getDataRows();
    }
}
