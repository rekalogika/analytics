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

abstract class NodeBlock extends Block
{
    private readonly ?BlockGroup $parent;

    /**
     * @param int<0,max> $level
     */
    protected function __construct(
        private readonly TreeNodeDecorator $node,
        ?Block $parent,
        int $level,
        BlockContext $context,
    ) {
        parent::__construct($level, $context);

        if ($parent !== null && !$parent instanceof BlockGroup) {
            throw new \InvalidArgumentException(\sprintf(
                'Parent must be an instance of %s, %s given.',
                BlockGroup::class,
                get_debug_type($parent),
            ));
        }

        $this->parent = $parent;
    }

    final public function getTreeNode(): TreeNodeDecorator
    {
        return $this->node;
    }

    final public function getParentBlock(): ?BlockGroup
    {
        return $this->parent;
    }
}
