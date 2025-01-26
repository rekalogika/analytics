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

namespace Rekalogika\Analytics\Query;

use Rekalogika\Analytics\PivotTable\BranchNode;

class SummaryResult implements BranchNode
{
    /**
     * @param list<SummaryItem|SummaryLeafItem> $items
     */
    public function __construct(
        private readonly array $items,
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return '';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return '';
    }

    #[\Override]
    public function getItem(): mixed
    {
        return '';
    }

    #[\Override]
    public function getChildren(): array
    {
        return $this->items;
    }
}
