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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper;

use Rekalogika\Analytics\Contracts\Exception\InterpolationOverflowException;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultDimension;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultMeasure;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output\DefaultTreeNode;

final class TreeNodeFactory
{
    private int $fillingNodesCount = 0;

    public function __construct(
        private readonly int $fillingNodesLimit,
        private readonly RowCollection $rowCollection,
    ) {}

    /**
     * @param class-string $summaryClass
     */
    public function createBranchNode(
        string $summaryClass,
        string $childrenKey,
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        ItemCollection $itemCollection,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: $childrenKey,
            dimension: $dimension,
            parent: $parent,
            measure: null,
            itemCollection: $itemCollection,
            null: false,
            treeNodeFactory: $this,
            rowCollection: $this->rowCollection,
        );
    }

    /**
     * @param class-string $summaryClass
     */
    public function createLeafNode(
        string $summaryClass,
        ?DefaultTreeNode $parent,
        DefaultDimension $dimension,
        ItemCollection $itemCollection,
        DefaultMeasure $measure,
    ): DefaultTreeNode {
        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: null,
            dimension: $dimension,
            parent: $parent,
            itemCollection: $itemCollection,
            measure: $measure,
            null: false,
            treeNodeFactory: $this,
            rowCollection: $this->rowCollection,
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
        ItemCollection $itemCollection,
        ?DefaultMeasure $measure,
    ): DefaultTreeNode {
        if ($this->fillingNodesCount >= $this->fillingNodesLimit) {
            throw new InterpolationOverflowException($this->fillingNodesLimit);
        }

        $this->fillingNodesCount++;

        return new DefaultTreeNode(
            summaryClass: $summaryClass,
            childrenKey: $childrenKey,
            dimension: $dimension,
            parent: $parent,
            itemCollection: $itemCollection,
            measure: $measure,
            null: true,
            treeNodeFactory: $this,
            rowCollection: $this->rowCollection,
        );
    }
}
