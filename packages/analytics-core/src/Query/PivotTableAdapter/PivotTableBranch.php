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

use Rekalogika\Analytics\PivotTable\BranchNode;
use Rekalogika\Analytics\Query\SummaryItem;
use Rekalogika\Analytics\Query\SummaryLeafItem;

final readonly class PivotTableBranch implements BranchNode
{
    public function __construct(
        private SummaryItem $item,
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return $this->item->getKey();
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->item->getLegend();
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->item->getItem();
    }

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->item->getChildren() as $item) {
            if ($item instanceof SummaryItem) {
                yield new PivotTableBranch($item);
            } elseif ($item instanceof SummaryLeafItem) {
                yield new PivotTableLeaf($item);
            }
        }
    }
}
