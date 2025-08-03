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

namespace Rekalogika\PivotTable\Decorator;

use Rekalogika\PivotTable\Contracts\TreeNode;

abstract class BaseTreeNodeDecorator implements TreeNode
{
    public function __construct(
        private readonly TreeNode $node,
    ) {}

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->node->isLeaf();
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->node->getKey();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->node->getLegend();
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->node->getItem();
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->node->getValue();
    }

    /**
     * @param int<1,max> $level
     * @return iterable<TreeNodeDecorator>
     */
    #[\Override]
    abstract public function getChildren(int $level = 1): iterable;
}
