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
use Rekalogika\Analytics\PivotTable\Util\TreePropertyMap;
use Rekalogika\PivotTable\Contracts\Tree\BranchNode;

final readonly class PivotTableAdapter implements BranchNode
{
    public static function adapt(TreeNode $node): self
    {
        return new self($node);
    }

    private function __construct(
        private TreeNode $node,
        private TreePropertyMap $propertyMap = new TreePropertyMap(),
    ) {}

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
    public function getChildren(): iterable
    {
        foreach ($this->node as $item) {
            if ($item->isNull()) {
                continue;
            }

            if ($item->getMeasure() === null) {
                yield new PivotTableAdapter($item, $this->propertyMap);
            } else {
                yield new PivotTableAdapterLeaf($item, $this->propertyMap);
            }
        }
    }

    #[\Override]
    public function getSubtotals(): iterable
    {
        $subtotals = $this->node->getSubtotals();

        foreach ($subtotals as $subtotal) {
            yield SubtotalAdapter::adapt($subtotal);
        }
    }

    public function getTreeNode(): TreeNode
    {
        return $this->node;
    }
}
