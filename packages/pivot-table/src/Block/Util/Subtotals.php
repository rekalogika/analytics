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

namespace Rekalogika\PivotTable\Block\Util;

use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;

/**
 * @implements \IteratorAggregate<SubtotalNode>
 */
final class Subtotals implements \IteratorAggregate, \Countable
{
    /**
     * @var list<SubtotalNode>
     */
    private readonly array $nodes;

    /**
     * @var list<SubtotalNode>
     */
    private array $remainingNodes;

    public function __construct(TreeNode $node)
    {
        $this->nodes = $this->remainingNodes
            = iterator_to_array($node->getSubtotals(), false);
    }

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

    public function takeOne(): SubtotalNode
    {
        $node = array_shift($this->remainingNodes);

        if ($node === null) {
            throw new \RuntimeException('No more subtotals available.');
        }

        return $node;
    }
}
