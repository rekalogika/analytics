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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\TreeNode;

final readonly class BlockContext
{
    /**
     * @param list<list<TreeNode>> $distinct
     * @param list<string> $pivotedDimensions
     * @param list<string> $superfluousLegends
     */
    public function __construct(
        private array $distinct,
        private array $pivotedDimensions = [],
        private array $superfluousLegends = [],
    ) {}

    /**
     * @return list<TreeNode>
     */
    public function getDistinctNodesOfLevel(int $level): array
    {
        return $this->distinct[$level] ?? throw new \LogicException('Unknown level');
    }

    public function isPivoted(TreeNode $treeNode): bool
    {
        return \in_array($treeNode->getKey(), $this->pivotedDimensions, true);
    }

    public function hasSuperfluousLegend(TreeNode $treeNode): bool
    {
        return \in_array($treeNode->getKey(), $this->superfluousLegends, true);
    }
}
