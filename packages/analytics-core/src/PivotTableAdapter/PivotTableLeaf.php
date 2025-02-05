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

namespace Rekalogika\Analytics\PivotTableAdapter;

use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\Query\SummaryNode;

final readonly class PivotTableLeaf implements LeafNode
{
    public function __construct(
        private SummaryNode $node,
    ) {
        if (!$node->isLeaf()) {
            throw new \InvalidArgumentException('Item must be a leaf');
        }
    }

    public function getValue(): mixed
    {
        return $this->node->getValue();
    }

    public function getRawValue(): int|float|null
    {
        return $this->node->getRawValue();
    }

    public function getKey(): string
    {
        return $this->node->getKey();
    }

    public function getLegend(): mixed
    {
        return $this->node->getLegend();
    }

    public function getItem(): mixed
    {
        return $this->node->getItem();
    }
}
