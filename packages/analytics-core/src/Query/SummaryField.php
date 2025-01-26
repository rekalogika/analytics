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

use Rekalogika\Analytics\PivotTable\TreeNode;

abstract class SummaryField implements TreeNode
{
    private ?SummaryItem $parent = null;

    protected function __construct(
        private readonly string $key,
        private readonly mixed $legend,
        private readonly mixed $item,
    ) {}

    public function isEqual(self $other): bool
    {
        return $this->key === $other->key
            && $this->item === $other->item;
        ;
    }

    #[\Override]
    public function getLegend(): mixed
    {
        return $this->legend;
    }

    #[\Override]
    public function getItem(): mixed
    {
        return $this->item;
    }

    public function setParent(SummaryItem $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?SummaryItem
    {
        return $this->parent;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }
}
