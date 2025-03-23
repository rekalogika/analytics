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

use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
{
    #[\Override]
    protected function createHeaderRows(): DefaultRows
    {
        $firstChildren = $this->getChildren()[0] ?? null;

        if ($firstChildren === null) {
            $firstChildren = $this->getBalancedChildren()[0];
        }

        $childBlock = $this->createBlock($firstChildren, $this->getLevel() + 1);

        return $childBlock->getHeaderRows();
    }

    #[\Override]
    protected function createDataRows(): DefaultRows
    {
        $dataRows = new DefaultRows([]);

        foreach ($this->getChildren() as $childNode) {
            $childBlock = $this->createBlock($childNode, $this->getLevel() + 1);
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        return $dataRows;
    }
}
