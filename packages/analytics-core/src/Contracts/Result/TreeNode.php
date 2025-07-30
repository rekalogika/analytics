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
     * @param ?string $name The children's dimension name, if null, it will be
     * the next dimension according to the query.
     */
    public function getChildren(?string $name = null): TreeNodes;

    public function getMeasure(): Measure;

    public function getSubtotals(): Measures;

    public function traverse(mixed ...$members): ?TreeNode;

    /**
     * Determine if this TreeNode was created to balance the tree, and does not
     * result from the query. If true, this node always leads to a dead end, and
     * won't have any measure on its leaves
     */
    public function isNull(): bool;
}
