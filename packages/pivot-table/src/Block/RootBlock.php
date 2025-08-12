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

use Rekalogika\PivotTable\Block\Model\CubeDecorator;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

final class RootBlock extends BranchBlock
{
    private bool $isEmpty;

    protected function __construct(
        CubeDecorator $cube,
        BlockContext $context,
    ) {
        parent::__construct(
            cube: $cube,
            parent: null,
            context: $context,
        );

        $nextKey = $context->getNextKey();

        if ($nextKey === null) {
            $this->isEmpty = true;
        } else {
            $children = iterator_to_array($cube->drillDown($nextKey));

            if ($children === []) {
                $this->isEmpty = true;
            } else {
                $this->isEmpty = false;
            }
        }
    }

    #[\Override]
    public function getHeaderRows(): DefaultRows
    {
        if ($this->isEmpty) {
            return new DefaultRows([], $this->getElementContext());
        }

        return $this->getChildrenBlockGroup()->getHeaderRows();
    }

    #[\Override]
    public function getDataRows(): DefaultRows
    {
        if ($this->isEmpty) {
            return new DefaultRows([], $this->getElementContext());
        }

        return $this->getChildrenBlockGroup()->getDataRows();
    }
}
