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
use Rekalogika\Analytics\Query\SummaryResult;

final readonly class PivotTableAdapter implements BranchNode
{
    public function __construct(
        private SummaryResult $result,
    ) {}

    #[\Override]
    public function getKey(): string
    {
        return '';
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return null;
    }

    #[\Override]
    public function getItem(): mixed
    {
        return null;
    }

    #[\Override]
    public function getChildren(): iterable
    {
        foreach ($this->result->getChildren() as $item) {
            if ($item instanceof SummaryItem) {
                yield new PivotTableBranch($item);
            } else {
                yield new PivotTableLeaf($item);
            }
        }
    }
}
