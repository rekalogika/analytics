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

namespace Rekalogika\Analytics\PivotTable\Adapter\Tree;

use Rekalogika\Analytics\Contracts\Result\TreeNodes;
use Rekalogika\Analytics\PivotTable\Util\TreePropertyMap;
use Rekalogika\PivotTable\Contracts\Tree\TreeNodes as PivotTableTreeNodes;

/**
 * @implements \IteratorAggregate<PivotTableTreeNodeAdapter>
 */
final readonly class PivotTableTreeNodesAdapter implements PivotTableTreeNodes, \IteratorAggregate
{
    /**
     * @var list<PivotTableTreeNodeAdapter>
     */
    private array $nodes;

    public function __construct(
        TreeNodes $nodes,
        TreePropertyMap $propertyMap,
    ) {
        $newNodes = [];

        foreach ($nodes as $node) {
            if ($node->isNull()) {
                continue;
            }

            $newNodes[] = new PivotTableTreeNodeAdapter($node, $propertyMap);
        }

        $this->nodes = $newNodes;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->nodes);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->nodes);
    }
}
