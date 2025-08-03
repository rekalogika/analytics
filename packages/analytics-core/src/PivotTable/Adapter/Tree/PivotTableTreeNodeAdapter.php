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

namespace Rekalogika\Analytics\PivotTable\Adapter\Tree;

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\PivotTable\Model\Tree\TreeValue;
use Rekalogika\Analytics\PivotTable\Util\TreePropertyMap;
use Rekalogika\PivotTable\Contracts\TreeNode as PivotTableTreeNode;

final readonly class PivotTableTreeNodeAdapter implements PivotTableTreeNode
{
    public static function adapt(TreeNode $node): self
    {
        return new self($node, new TreePropertyMap());
    }

    public function __construct(
        private TreeNode $node,
        private TreePropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->node->getDimensionNames() === [];
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->node->getName();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->propertyMap->getLabel($this->node);
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->propertyMap->getMember($this->node);
    }

    #[\Override]
    public function getValue(): mixed
    {
        return new TreeValue($this->node);
    }

    #[\Override]
    public function getChildren(int $level = 1): \Traversable
    {
        foreach ($this->node->getChildren($level) as $child) {
            if ($child->isNull()) {
                continue;
            }

            yield new self($child, $this->propertyMap);
        }

        // return new PivotTableTreeNodesAdapter(
        //     nodes: $this->node->getChildren($level),
        //     propertyMap: $this->propertyMap,
        // );
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
