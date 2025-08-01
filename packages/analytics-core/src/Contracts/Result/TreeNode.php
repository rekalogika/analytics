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

namespace Rekalogika\Analytics\Contracts\Result;

use Rekalogika\Analytics\Contracts\Collection\OrderedMapCollection;

/**
 * Represent a node in a tree.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends OrderedMapCollection<mixed,TreeNode>
 */
interface TreeNode extends OrderedMapCollection, Dimension
{
    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * Get the tuple of this node, which is a collection of dimensions from the
     * root to this node.
     */
    public function getTuple(): Tuple;

    /**
     * The dimension names of the descendant nodes of this node. Empty means
     * this node is a leaf node.
     *
     * @return list<string>
     */
    public function getDimensionNames(): array;

    /**
     * Gets children of this node, with the specified dimension name. The
     * dimension name does not have to be the immediate dimension according to
     * the query. But must be one of the descendant dimension names.
     *
     * @param int<1,max>|int<min,-1>|string $name If string, gets the children
     * with that dimension name. The name must be one of the descendant
     * dimension names. If int, skips the first $name children and returns the
     * rest. If negative, skips to the last $name children. The default is 1,
     * which means the next immediate dimension according to the query.
     */
    public function getChildren(int|string $name = 1): TreeNodes;

    public function getMeasure(): Measure;

    public function getMeasures(): Measures;

    public function traverse(mixed ...$members): ?TreeNode;

    /**
     * Determine if this TreeNode was created to balance the tree, and does not
     * result from the query. If true, this node always leads to a dead end, and
     * won't have any measure on its leaves
     */
    public function isNull(): bool;
}
