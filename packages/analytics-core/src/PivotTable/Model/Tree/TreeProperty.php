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

namespace Rekalogika\Analytics\PivotTable\Model\Tree;

use Rekalogika\Analytics\Contracts\Result\TreeNode;

abstract readonly class TreeProperty
{
    final public function __construct(private TreeNode $node) {}

    abstract public function getContent(): mixed;

    final public function getNode(): TreeNode
    {
        return $this->node;
    }
}
