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

use Rekalogika\Analytics\PivotTable\TreeNode;

abstract readonly class NullTreeNode implements TreeNode
{
    final public function __construct(
        private string $key,
        private mixed $legend,
        private mixed $item,
    ) {}

    abstract public function isLeaf(): bool;

    #[\Override]
    public function getKey(): string
    {
        return $this->key;
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

    #[\Override]
    public function getChildren(): array
    {
        return [];
    }

    public function getValue(): mixed
    {
        return null;
    }

    public function getRawValue(): int|float|null
    {
        return null;
    }
}
