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

use Rekalogika\Analytics\PivotTable\BranchNode;
use Rekalogika\Analytics\Query\TreeNode;

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
        return $this->node->getMember();
    }

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->node as $item) {
            if ($item->isLeaf()) {
                yield new PivotTableLeaf($item);
            } else {
                yield new PivotTableBranch($item);
            }
        }
    }
}
