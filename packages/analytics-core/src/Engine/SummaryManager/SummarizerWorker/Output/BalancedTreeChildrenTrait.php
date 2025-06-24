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

trait BalancedTreeChildrenTrait
{
    /**
     * @var null|list<DefaultTreeNode>
     */
    private ?array $balancedChildren = null;

    private ?\Throwable $balancedChildrenException = null;

    /**
     * @return list<DefaultTreeNode>
     */
    private function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        if ($this->balancedChildrenException !== null) {
            throw $this->balancedChildrenException;
        }

        if ($this->childrenKey === null) {
            return $this->children;
        }

        try {
            $childrenDimensions = $this->items
                ->getDimensions($this->childrenKey);

            $balancedChildren = [];

            foreach ($childrenDimensions as $dimension) {
                $child = $this->getChildEqualTo($dimension);

                if ($child === null) {
                    /** @psalm-suppress InaccessibleProperty */
                    $child = $this->treeNodeFactory->createFillingNode(
                        summaryClass: $this->summaryClass,
                        childrenKey: $this->items->getKeyAfter($this->childrenKey),
                        dimension: $dimension,
                        parent: $this instanceof DefaultTreeNode ? $this : null,
                        measure: null,
                        items: $this->items,
                    );
                }

                $balancedChildren[] = $child;
            }

            return $this->balancedChildren = $balancedChildren;
        } catch (\Throwable $e) {
            $this->balancedChildrenException = $e;
            throw $e;
        }
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
