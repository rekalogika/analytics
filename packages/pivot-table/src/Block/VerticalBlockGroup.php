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
    protected function createPrototypeCubes(): array
    {
        return [];
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        return $this->getOneChildBlock()->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $dataRows = new DefaultRows([], $this->getElementContext());

        // add a data row for each of the child blocks
        foreach ($this->getChildBlocks() as $childBlock) {
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        return $dataRows;
    }
}
