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

final class TreeNodeDecoratorRepository
{
    /**
     * @var \WeakMap<TreeNode,BaseTreeNodeDecorator>
     */
    private \WeakMap $treeNodeDecorators;

    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->treeNodeDecorators = new \WeakMap();
    }

    public function decorate(TreeNode $node): TreeNodeDecorator
    {
        if ($node instanceof TreeNodeDecorator) {
            return $node;
        }

        if (isset($this->treeNodeDecorators[$node])) {
            /** @var TreeNodeDecorator */
            return $this->treeNodeDecorators[$node];
        }

        return  $this->treeNodeDecorators[$node] =
            new TreeNodeDecorator(
                node: $node,
                repository: $this,
            );
    }
}
