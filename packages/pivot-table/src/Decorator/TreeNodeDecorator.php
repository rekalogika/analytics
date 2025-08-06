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

namespace Rekalogika\PivotTable\Decorator;

use Rekalogika\PivotTable\Contracts\TreeNode;
use Rekalogika\PivotTable\Decorator\Internal\ItemToTreeNodeDecoratorMap;
use Rekalogika\PivotTable\Implementation\TreeNode\NullTreeNode;
use Rekalogika\PivotTable\Implementation\TreeNode\SubtotalTreeNode;
use Rekalogika\PivotTable\Util\TreeNodeDebugger;

final class TreeNodeDecorator extends BaseTreeNodeDecorator
{
    public static bool $debug = false;

    /**
     * @var array<string,mixed>
     */
    public array $debugData;

    /**
     * @var array<int,list<self>>
     */
    private array $children = [];

    /**
     * @var array<string,list<self>>
     */
    private array $drillDown = [];

    public static function decorate(TreeNode $node): self
    {
        $repository = new TreeNodeDecoratorRepository();

        return $repository->decorate($node);
    }

    public function __construct(
        private readonly TreeNode $node,
        private readonly TreeNodeDecoratorRepository $repository,
        private readonly null|self $parent = null,
    ) {
        if ($node instanceof self) {
            throw new \InvalidArgumentException('Cannot redecorate a TreeNodeDecorator instance.');
        }

        if (self::$debug) {
            $this->debugData = TreeNodeDebugger::debug($node);
        } else {
            $this->debugData = [];
        }

        parent::__construct($node);
    }

    public function withParent(self $parent): self
    {
        return new self(
            node: $this->node,
            repository: $this->repository,
            parent: $parent,
        );
    }

    public function isSubtotal(): bool
    {
        return $this->node instanceof SubtotalTreeNode;
    }

    /**
     * @param int<1,max> $level
     * @return list<self>
     */
    #[\Override]
    public function getChildren(int $level = 1): array
    {
        if (isset($this->children[$level])) {
            return $this->children[$level];
        }

        $result = [];

        foreach ($this->node->getChildren($level) as $child) {
            $result[] = $this->repository
                ->decorate($child)
                ->withParent($this);
        }

        return $this->children[$level] = $result;
    }

    /**
     * @return iterable<self>
     */
    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        if (isset($this->drillDown[$dimensionName])) {
            return $this->drillDown[$dimensionName];
        }

        $children = $this->node->drillDown($dimensionName);

        $result = [];

        foreach ($children as $child) {
            $result[] = $this->repository
                ->decorate($child)
                ->withParent($this);
        }

        return $this->drillDown[$dimensionName] = $result;
    }

    /**
     * Gets the unique child items from the perspective of the parent node.
     *
     * @param int<1,max> $childLevel 1 means the immediate children, 2 means
     * grandchildren, etc.
     * @param int<0,max> $parentLevel 0 means the current node, 1 means the parent node,
     * etc.
     * @return list<self>
     */
    private function getChildrenSeenByParent(int $childLevel, int $parentLevel): array
    {
        $parent = $this->getParentByLevel($parentLevel);

        return $parent->getChildren($childLevel + $parentLevel);
    }

    // /**
    //  * @param int<1,max> $childLevel 1 means the immediate children, 2 means
    //  * grandchildren, etc.
    //  * @return list<self>
    //  */
    // private function getBalancedChildrenFromParentKey(
    //     int $childLevel,
    //     string $parentKey
    // ): array {
    //     $parentLevel = $this->getParentLevelByKey($parentKey);

    //     if ($parentLevel === null) {
    //         return $this->getChildren($childLevel);
    //     }

    //     if ($parentLevel < 0) {
    //         throw new \LogicException(\sprintf(
    //             'Parent level %d is invalid for parent key %s in node %s.',
    //             $parentLevel,
    //             $parentKey,
    //             $this->getKey(),
    //         ));
    //     }

    //     return $this->getBalancedChildren($childLevel, $parentLevel);
    // }

    // /**
    //  * Gets the first parent node that is not pivoted, or null if all parents
    //  * are pivoted or if the parent is in the list of pivoted dimensions.
    //  *
    //  * @param list<string> $pivotedDimensions
    //  */
    // private function getFirstNonPivotedParent(array $pivotedDimensions): self
    // {
    //     if (!\in_array($this->getKey(), $pivotedDimensions, true)) {
    //         return $this;
    //     }

    //     if ($this->parent === null) {
    //         return $this;
    //     }

    //     return $this->parent->getFirstNonPivotedParent($pivotedDimensions);
    // }

    // private function getFirstNonPivotedParentLevel(array $pivotedDimensions): ?int
    // {
    //     if (!\in_array($this->getKey(), $pivotedDimensions, true)) {
    //         return 0;
    //     }

    //     if ($this->parent === null) {
    //         return null;
    //     }

    //     $parentLevel = $this->parent->getFirstNonPivotedParentLevel($pivotedDimensions);

    //     if ($parentLevel === null) {
    //         return null;
    //     }

    //     return $parentLevel + 1;
    // }

    // private function getParentLevelByKey(string $parentKey): ?int
    // {
    //     $parent = $this->parent;

    //     if ($parent === null) {
    //         return null;
    //     }

    //     if ($parent->getKey() === $parentKey) {
    //         return 0;
    //     }

    //     $parentResult = $parent->getParentLevelByKey($parentKey);

    //     if ($parentResult === null) {
    //         return null;
    //     }

    //     return $parentResult + 1;
    // }

    /**
     * @param list<self> $children
     * @param non-empty-list<self> $childrenPrototype
     * @return list<self>
     */
    private function doBalanceChildren(array $children, array $childrenPrototype): array
    {
        // create a map of children items to nodes
        $childrenItemsToNodes = ItemToTreeNodeDecoratorMap::create($children);

        // create result
        $result = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($childrenPrototype as $child) {
            $currentItem = $child->getItem();

            if ($childrenItemsToNodes->exists($currentItem)) {
                $result[] = $childrenItemsToNodes
                    ->get($currentItem)
                    ->withParent($this);
            } else {
                $null = NullTreeNode::fromInterface($child);

                $decorated = $this->repository
                    ->decorate($null)
                    ->withParent($this);

                $result[] = $decorated;
            }
        }

        return $result;
    }

    /**
     *
     * @param int<1,max> $childLevel 1 means the immediate children, 2 means
     * grandchildren, etc.
     * @param int<0,max> $parentLevel 0 means the current node, 1 means the parent node,
     * etc.
     * @return list<self>
     */
    public function getBalancedChildren(int $childLevel, int $parentLevel): array
    {
        $children = $this->getChildren($childLevel);
        $childrenSeenByParent = $this->getChildrenSeenByParent($childLevel, $parentLevel);

        if (\count($childrenSeenByParent) === 0) {
            return [];
        }

        $result = $this->doBalanceChildren($children, $childrenSeenByParent);

        if ($result === []) {
            throw new \LogicException(\sprintf(
                'No children found for child level %d and parent level %d in node %s.',
                $childLevel,
                $parentLevel,
                $this->getKey(),
            ));
        }

        return $result;
    }

    /**
     * Gets the balanced children from a non-pivoted parent node.
     *
     * @param int<1,max> $childLevel 1 means the immediate children, 2 means
     * grandchildren, etc.
     * @param list<string> $pivotedDimensions The list of pivoted dimensions.
     * @return list<self>
     */
    public function getBalancedChildrenFromNonPivotedParent(
        int $childLevel,
        array $pivotedDimensions,
    ): array {
        $children = $this->getChildren($childLevel);

        // determine the suitable parent for getting the prototypes

        $parent = $this;
        $parentLevel = 0;

        // @todo fix this logic

        while (true) {
            if ($parent->parent === null) {
                // if the parent is null, we are at the root node
                break;
            }

            $parent = $parent->parent;
            $parentLevel++;
        }

        // while (true) {
        //     if ($parent->parent === null) {
        //         // if the parent is null, we are at the root node
        //         break;
        //     }

        //     if (!\in_array($parent->parent->getKey(), $pivotedDimensions, true)) {
        //         break;
        //     }

        //     // if ($parent->parent === null) {
        //     //     break;
        //     // }

        //     $parent = $parent->parent;
        //     $parentLevel++;
        // }

        // if ($parent->getKey() === '@values' && $parent->parent !== null) {
        //     $parent = $parent->parent;
        //     $parentLevel++;
        // }

        // end

        $childrenPrototype = $parent->getChildren($childLevel + $parentLevel);

        if (\count($childrenPrototype) === 0) {
            throw new \LogicException(\sprintf(
                'No children found for child level %d in non-pivoted parent node %s.',
                $childLevel,
                $parent->getKey(),
            ));
        }

        return $this->doBalanceChildren($children, $childrenPrototype);
    }

    /**
     * Gets the parent node at the specified level.
     *
     * @param int<0,max> $level The level of the parent node to retrieve. 0
     * means the current node, 1 means the immediate parent, 2 means the
     * grandparent, etc.
     */
    private function getParentByLevel(int $level): self
    {
        $current = $this;

        for ($i = 1; $i <= $level; $i++) {
            if ($current->parent === null) {
                throw new \LogicException('Cannot get parent by level: no parent found at level ' . $level);
            }
            $current = $current->parent;
        }

        return $current;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
