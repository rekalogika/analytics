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

namespace Rekalogika\Analytics\Contracts;

/**
 * Represent a node in the query result.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<mixed,TreeNode>
 */
interface TreeNode extends \Traversable, \Countable, Dimension
{
    public function getMeasure(): ?Measure;

    public function traverse(mixed ...$members): ?TreeNode;

    /**
     * Determine if this TreeNode is created to balance the tree, and does not
     * result from the query. If true, this node always leads to a deadend, and
     * won't have any measure on its leaves
     */
    public function isNull(): bool;
}
