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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Exception\OverflowException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;

final class DefaultTreeNodeFactory
{
    private int $fillingNodesCount = 0;

    public function __construct(
        private readonly int $fillingNodesLimit,
    ) {}

    public function createBranchNode(
        string $childrenKey,
        DefaultDimension $dimension,
        Items $items,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            childrenKey: $childrenKey,
            dimension: $dimension,
            measure: null,
            items: $items,
            null: false,
            treeNodeFactory: $this,
        );
    }

    public function createLeafNode(
        DefaultDimension $dimension,
        Items $items,
        DefaultMeasure $measure,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            childrenKey: null,
            dimension: $dimension,
            items: $items,
            measure: $measure,
            null: false,
            treeNodeFactory: $this,
        );
    }

    public function createFillingNode(
        ?string $childrenKey,
        DefaultDimension $dimension,
        Items $items,
        ?DefaultMeasure $measure,
    ): DefaultTreeNode {
        if ($this->fillingNodesCount >= $this->fillingNodesLimit) {
            throw new OverflowException('Maximum filling nodes reached');
        }

        $this->fillingNodesCount++;

        return new DefaultTreeNode(
            childrenKey: $childrenKey,
            dimension: $dimension,
            items: $items,
            measure: $measure,
            null: true,
            treeNodeFactory: $this,
        );
    }
}
