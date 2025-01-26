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

class SummaryItem extends SummaryField implements BranchNode
{
    /**
     * @var list<SummaryItem|SummaryLeafItem>
     */
    private array $children = [];

    public function __construct(
        string $key,
        mixed $legend,
        mixed $name,
    ) {
        parent::__construct(
            key: $key,
            legend: $legend,
            item: $name,
        );
    }

    public function __clone()
    {
        $this->children = [];
    }

    public function addChild(SummaryItem|SummaryLeafItem $item): void
    {
        $this->children[] = $item;
        $item->setParent($this);
    }

    #[\Override]
    public function getChildren(): array
    {
        return $this->children;
    }
}
