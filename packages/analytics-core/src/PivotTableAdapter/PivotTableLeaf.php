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

namespace Rekalogika\Analytics\PivotTableAdapter;

use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\Query\TreeNode;

final readonly class PivotTableLeaf implements LeafNode
{
    public function __construct(
        private TreeNode $node,
    ) {
        if (!$node->isLeaf()) {
            throw new \InvalidArgumentException('Item must be a leaf');
        }
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->node->getMeasure()?->getValue();
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->node->getKey();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->node->getLabel();
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->node->getDisplayMember();
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
