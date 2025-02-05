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

namespace Rekalogika\Analytics\PivotTable\Model;

use Rekalogika\Analytics\PivotTable\LeafNode;

final readonly class NullLeafNode extends NullTreeNode implements LeafNode
{
    public static function fromInterface(LeafNode $node): self
    {
        return new self(
            key: $node->getKey(),
            legend: $node->getLegend(),
            item: $node->getItem(),
        );
    }

    public function isLeaf(): bool
    {
        return true;
    }
}
