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

use Rekalogika\Analytics\Contracts\Measure;
use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\Analytics\PivotTable\LeafNode;

final readonly class PivotTableLeaf implements LeafNode
{
    private Measure $measure;

    public function __construct(
        private TreeNode $node,
    ) {
        $measure = $node->getMeasure();

        if ($measure === null) {
            throw new \InvalidArgumentException('Item must be a leaf');
        }

        $this->measure = $measure;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->measure->getValue();
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
