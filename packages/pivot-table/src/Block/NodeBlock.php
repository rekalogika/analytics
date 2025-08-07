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

use Rekalogika\PivotTable\TableFramework\Cube;

abstract class NodeBlock extends Block
{
    private readonly ?BlockGroup $parent;

    protected function __construct(
        private readonly Cube $cube,
        ?Block $parent,
        BlockContext $context,
    ) {
        parent::__construct($context);

        if ($parent !== null && !$parent instanceof BlockGroup) {
            throw new \InvalidArgumentException(\sprintf(
                'Parent must be an instance of %s, %s given.',
                BlockGroup::class,
                get_debug_type($parent),
            ));
        }

        $this->parent = $parent;
    }

    final public function getCube(): Cube
    {
        return $this->cube;
    }

    final public function getParentBlock(): ?BlockGroup
    {
        return $this->parent;
    }
}
