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

use Rekalogika\Analytics\Bundle\UI\PivotTableAdapter\Wrapper\NodeWrapperFactory;
use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\PivotTable\Contracts\BranchNode;

final readonly class UIPivotTableBranch implements BranchNode
{
    public function __construct(
        private TreeNode $node,
        private NodeWrapperFactory $nodeWrapperFactory,
    ) {}

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

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->node as $item) {
            if ($item->isNull()) {
                continue;
            }

            if ($item->getMeasure() === null) {
                yield new UIPivotTableBranch($item, $this->nodeWrapperFactory);
            } else {
                yield new UIPivotTableLeaf($item, $this->nodeWrapperFactory);
            }
        }
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
