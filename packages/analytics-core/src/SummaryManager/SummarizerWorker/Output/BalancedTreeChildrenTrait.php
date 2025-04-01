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

trait BalancedTreeChildrenTrait
{
    /**
     * @var null|list<DefaultTreeNode>
     */
    private ?array $balancedChildren = null;

    /**
     * @return list<DefaultTreeNode>
     */
    private function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        if ($this->childrenKey === null) {
            return $this->children;
        }

        $childrenDimensions = $this->items
            ->getDimensions($this->childrenKey);

        $balancedChildren = [];

        foreach ($childrenDimensions as $dimension) {
            $child = $this->getChildEqualTo($dimension);

            if ($child === null) {
                // continue;
                $child = $this->treeNodeFactory->createFillingNode(
                    childrenKey: $this->items->getKeyAfter($this->childrenKey),
                    dimension: $dimension,
                    measure: null,
                    items: $this->items,
                );
            }

            if ($this instanceof DefaultTreeNode) {
                $child->setParent($this);
            }

            $balancedChildren[] = $child;
        }

        return $this->balancedChildren = $balancedChildren;
    }

    private function getChildEqualTo(
        DefaultDimension $dimension,
    ): ?DefaultTreeNode {
        foreach ($this->children as $child) {
            if ($child->isEqual($dimension)) {
                return $child;
            }
        }

        return null;
    }
}
