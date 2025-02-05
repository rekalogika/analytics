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

namespace Rekalogika\Analytics\Query\PivotTableAdapter;

use Rekalogika\Analytics\PivotTable\LeafNode;
use Rekalogika\Analytics\Query\SummaryLeafItem;

final readonly class PivotTableLeaf implements LeafNode
{
    public function __construct(
        private SummaryLeafItem $item,
    ) {}

    public function getValue(): mixed
    {
        return $this->item->getValue();
    }

    public function getRawValue(): int|float|null
    {
        return $this->item->getRawValue();
    }

    public function getKey(): string
    {
        return $this->item->getKey();
    }

    public function getLegend(): mixed
    {
        return $this->item->getLegend();
    }

    public function getItem(): mixed
    {
        return $this->item->getItem();
    }
}
