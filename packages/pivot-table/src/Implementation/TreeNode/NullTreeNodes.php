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

namespace Rekalogika\PivotTable\Implementation\TreeNode;

use Rekalogika\PivotTable\Contracts\Tree\TreeNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNodes;

/**
 * @implements \IteratorAggregate<TreeNode>
 */
final readonly class NullTreeNodes implements TreeNodes, \IteratorAggregate
{
    /**
     * @param list<TreeNode> $nodes
     */
    public function __construct(
        private array $nodes,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->nodes);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->nodes);
    }
}
