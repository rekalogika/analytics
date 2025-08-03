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

use Rekalogika\PivotTable\Decorator\TreeNodeDecorator;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class VerticalBlockGroup extends BlockGroup
{
    private ?DefaultRows $headerRows = null;

    private ?DefaultRows $dataRows = null;

    /**
     * @param int<0,max> $level
     */
    public function __construct(
        TreeNodeDecorator $node,
        ?TreeNodeDecorator $parentNode,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct(
            node: $node,
            parentNode: $parentNode,
            level: $level,
            context: $context,
        );
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->headerRows !== null) {
            return $this->headerRows;
        }

        return $this->headerRows = $this->getOneChildBlock()->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->dataRows !== null) {
            return $this->dataRows;
        }

        $dataRows = new DefaultRows([], $this->getElementContext());

        // add a data row for each of the child blocks
        foreach ($this->getChildBlocks() as $childBlock) {
            $dataRows = $dataRows->appendBelow($childBlock->getDataRows());
        }

        return $this->dataRows = $dataRows;
    }
}
