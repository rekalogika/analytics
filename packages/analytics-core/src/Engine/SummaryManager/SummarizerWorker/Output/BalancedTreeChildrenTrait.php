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

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;

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
            $childrenDimensions = $this->itemCollection
                ->getDimensions($this->childrenKey);

            $balancedChildren = [];

            foreach ($childrenDimensions as $dimension) {
                $child = $this->getChildEqualTo($dimension);

                if ($child === null) {
                    /** @psalm-suppress InaccessibleProperty */
                    $child = $this->treeNodeFactory->createFillingNode(
                        summaryClass: $this->summaryClass,
                        childrenKey: $this->itemCollection->getKeyAfter($this->childrenKey),
                        dimension: $dimension,
                        parent: $this instanceof DefaultTreeNode ? $this : null,
                        measure: null,
                        itemCollection: $this->itemCollection,
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

    public function getByKey(mixed $key): ?DefaultTreeNode
    {
        return $this->traverse($key);
    }

    public function getByIndex(int $index): ?DefaultTreeNode
    {
        $balancedChildren = $this->getBalancedChildren();

        return $balancedChildren[$index] ?? null;
    }

    public function hasKey(mixed $key): bool
    {
        try {
            $result = $this->traverse($key);

            return $result instanceof DefaultTreeNode;
        } catch (UnexpectedValueException) {
            return false;
        }
    }

    public function first(): ?DefaultTreeNode
    {
        $balancedChildren = $this->getBalancedChildren();

        return $balancedChildren[0] ?? null;
    }

    public function last(): ?DefaultTreeNode
    {
        $balancedChildren = $this->getBalancedChildren();

        $count = \count($balancedChildren);
        return $count > 0 ? $balancedChildren[$count - 1] : null;
    }
}
