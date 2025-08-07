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

final class PivotLeafBlock extends LeafBlock
{
    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $currentKey = $this->getContext()->getCurrentKey();

        if (
            $this->getContext()->isLegendSkipped($currentKey)
        ) {
            $cell = new DefaultHeaderCell(
                name: $currentKey,
                content: $this->getCube()->getMember($currentKey),
                context: $context,
            );
        } else {
            $cell = new DefaultDataCell(
                name: $currentKey,
                content: $this->getCube()->getMember($currentKey),
                context: $context,
            );
        }

        return DefaultRows::createFromCell($cell, $context);
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        $context = $this->getElementContext();
        $currentKey = $this->getContext()->getCurrentKey();

        $cell = new DefaultDataCell(
            name: $currentKey,
            content: $this->getCube()->getValue(),
            context: $context,
        );

        return DefaultRows::createFromCell($cell, $context);
    }
}
