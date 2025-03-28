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

namespace Rekalogika\Analytics\Bundle\UI\PivotTableAdapter;

use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeValue;
use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapperFactory;
use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\PivotTable\Contracts\LeafNode;

final readonly class UIPivotTableLeaf implements LeafNode
{
    public function __construct(
        private TreeNode $node,
        private NodeWrapperFactory $nodeWrapperFactory,
    ) {
        if (\count($node) > 0) {
            throw new \InvalidArgumentException('Item must be a leaf');
        }
    }

    #[\Override]
    public function getValue(): mixed
    {
        return new NodeValue($this->node);
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->node->getKey();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->nodeWrapperFactory->getLabel($this->node);
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->nodeWrapperFactory->getMember($this->node);
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
