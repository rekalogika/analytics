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
interface TreeNode extends OrderedMapCollection, Dimension, OrderedCell
{
    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * Gets children of this node, with the specified dimension name. The
     * dimension name does not have to be the immediate dimension according to
     * the query. But must be one of the descendant dimension names.
     *
     * @param int<1,max>|string $name If string, gets the children
     * with that dimension name. The name must be one of the descendant
     * dimension names. If int, skips the first $name children and returns the
     * rest. If negative, skips to the last $name children. The default is 1,
     * which means the next immediate dimension according to the query.
     */
    public function getChildren(int|string $name = 1): TreeNodes;

    public function traverse(mixed ...$members): ?TreeNode;

    /**
     * Determine if this TreeNode was created for interpolation, or to balance
     * the tree, and does not result from the query.
     */
    public function isNull(): bool;
}
