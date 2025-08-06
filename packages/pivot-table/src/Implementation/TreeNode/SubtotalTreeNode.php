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

namespace Rekalogika\PivotTable\Implementation\TreeNode;

use Rekalogika\PivotTable\Contracts\TreeNode;

final readonly class SubtotalTreeNode implements TreeNode
{
    public function __construct(
        private TreeNode $node,
        private string $childrenKey,
        private bool $isLeaf,
    ) {}


    #[\Override]
    public function isLeaf(): bool
    {
        return $this->isLeaf;
    }

    #[\Override]
    public function getPath(): array
    {
        $path = $this->node->getPath();
        $path[] = $this->childrenKey;

        return $path;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->childrenKey;
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return 'Total';
    }

    #[\Override]
    public function getItem(): mixed
    {
        return 'Total';
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->node->getValue();
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        return $this->node->drillDown($dimensionName);
    }
}
