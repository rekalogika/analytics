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

use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\Analytics\PivotTable\BranchNode;

final readonly class PivotTableBranch implements BranchNode
{
    public function __construct(
        private TreeNode $node,
    ) {}

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

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->node as $item) {
            if ($item->getMeasure() === null) {
                yield new PivotTableBranch($item);
            } else {
                yield new PivotTableLeaf($item);
            }
        }
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
