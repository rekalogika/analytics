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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Common\Exception\OverflowException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\Items;

final class DefaultTreeNodeFactory
{
    private int $fillingNodesCount = 0;

    public function __construct(
        private readonly int $fillingNodesLimit,
    ) {}

    /**
     * @param class-string $summaryClass
     */
    public function createBranchNode(
        string $summaryClass,
        string $childrenKey,
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        Items $items,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: $childrenKey,
            dimension: $dimension,
            parent: $parent,
            measure: null,
            items: $items,
            null: false,
            treeNodeFactory: $this,
        );
    }

    /**
     * @param class-string $summaryClass
     */
    public function createLeafNode(
        string $summaryClass,
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        Items $items,
        DefaultMeasure $measure,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: null,
            dimension: $dimension,
            parent: $parent,
            items: $items,
            measure: $measure,
            null: false,
            treeNodeFactory: $this,
        );
    }

    /**
     * @param class-string $summaryClass
     */
    public function createFillingNode(
        string $summaryClass,
        ?string $childrenKey,
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        Items $items,
        ?DefaultMeasure $measure,
    ): DefaultTreeNode {
        if ($this->fillingNodesCount >= $this->fillingNodesLimit) {
            throw new OverflowException('Maximum filling nodes reached');
        }

        $this->fillingNodesCount++;

        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: $childrenKey,
            dimension: $dimension,
            parent: $parent,
            items: $items,
            measure: $measure,
            null: true,
            treeNodeFactory: $this,
        );
    }
}
