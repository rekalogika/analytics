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

namespace Rekalogika\PivotTable\Contracts;

/**
 * @internal
 */
interface TreeNode
{
    /**
     * Determines if this node is a leaf node. A leaf node must not have any
     * children. But a node that has no children is not necessarily a leaf node.
     */
    public function isLeaf(): bool;

    /**
     * The path of this node in the tree. The path is a list of dimension names
     * that lead to this node. The last item in the path is the same as
     * `getKey()`
     *
     * @return list<string>
     */
    public function getPath(): array;

    /**
     * The key to identify a column. All nodes in the same column must have the
     * same key.
     */
    public function getKey(): string;

    /**
     * A user-visible legend of this node. Will be shown in the header row.
     */
    public function getLegend(): mixed;

    /**
     * The item that this node represents. This is usually a member of a
     * dimension. The special value `@values` is used to represent the
     * aggregated values of the column.
     */
    public function getItem(): mixed;

    /**
     * The value of this node. This is usually a numeric value that is
     * aggregated from the children nodes.
     */
    public function getValue(): mixed;

    /**
     * The children of this node. The level parameter indicates how many levels
     * down the tree to retrieve the children. For example, if the level is 1,
     * it will return the immediate children of this node. If the level is 2, it
     * will return the grandchildren.
     *
     * @param int<1,max> $level
     * @return iterable<TreeNode>
     */
    public function getChildren(int $level = 1): iterable;

    /**
     * @return iterable<TreeNode>
     */
    public function drillDown(string $dimensionName): iterable;
}
