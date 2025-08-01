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
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class NormalLeafBlock extends LeafBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $context = $this->getElementContext();

        $cell = new DefaultHeaderCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getLegend(),
            columnSpan: 2,
            context: $context,
        );

        return DefaultRows::createFromCell($cell, $context);
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $context = $this->getElementContext();

        $name = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getItem(),
            context: $context,
        );

        $value = new DefaultDataCell(
            name: $this->getTreeNode()->getKey(),
            content: $this->getTreeNode()->getValue(),
            context: $context,
        );

        $row = new DefaultRow([$name, $value], $context);

        return new DefaultRows([$row], $context);
    }
}
