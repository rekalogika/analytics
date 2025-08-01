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

use Rekalogika\PivotTable\Contracts\Tree\TreeNode;

final readonly class BlockContext
{
    /**
     * @param list<list<TreeNode>> $distinct
     * @param list<string> $pivotedDimensions
     * @param list<string> $skipLegends
     */
    public function __construct(
        private array $distinct,
        private array $pivotedDimensions = [],
        private array $skipLegends = [],
    ) {}

    /**
     * @return list<TreeNode>
     */
    public function getDistinctNodesOfLevel(int $level): array
    {
        $result =  $this->distinct[$level] ?? null;

        if ($result !== null) {
            return $result;
        }

        throw new \LogicException(\sprintf(
            'Distinct nodes of level %d not found. Available levels: %s',
            $level,
            implode(', ', array_keys($this->distinct)),
        ));
    }

    public function isPivoted(TreeNode $node): bool
    {
        return \in_array($node->getKey(), $this->pivotedDimensions, true);
    }

    public function isLegendSkipped(TreeNode $node): bool
    {
        return \in_array($node->getKey(), $this->skipLegends, true);
    }
}
