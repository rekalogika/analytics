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

abstract class BranchBlock extends NodeBlock
{
    final public function getChildrenBlockGroup(): BlockGroup
    {
        $context = $this->getContext();
        $nextKey = $context->getNextKey();

        if ($nextKey === null) {
            return new EmptyBlockGroup(
                cube: $this->getCube(),
                context: $context,
            );
        }

        if ($context->isNextKeyPivoted()) {
            return new HorizontalBlockGroup(
                cube: $this->getCube(),
                context: $context,
            );
        } else {
            return new VerticalBlockGroup(
                cube: $this->getCube(),
                context: $context,
            );
        }
    }
}
