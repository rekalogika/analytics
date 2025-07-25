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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Contracts\Tree\LeafNode;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;

/**
 * @extends NodeBlock<LeafNode>
 */
abstract class LeafBlock extends NodeBlock
{
    abstract public function getSubtotalHeaderRow(LeafNode $leafNode): DefaultRows;

    /**
     * @param list<LeafNode> $allLeafNodes
     */
    abstract public function getSubtotalDataRow(
        LeafNode $leafNode,
        array $allLeafNodes
    ): DefaultRows;
}
