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

namespace Rekalogika\PivotTable\Contracts\Tree;

interface TreeNode
{
    public function isLeaf(): bool;

    public function getKey(): string;

    public function getLegend(): mixed;

    public function getItem(): mixed;

    public function getValue(): mixed;

    /**
     * @param int<1,max> $level
     */
    public function getChildren(int $level = 1): TreeNodes;
}
