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

namespace Rekalogika\PivotTable\Implementation\TreeNode;

use Rekalogika\PivotTable\Contracts\LeafNode;

final readonly class NullLeafNode implements LeafNode
{
    public function __construct(
        private string $name,
        private mixed $legend,
        private mixed $item,
    ) {}

    public static function fromInterface(LeafNode $branchNode): self
    {
        return new self(
            name: $branchNode->getKey(),
            legend: $branchNode->getLegend(),
            item: $branchNode->getItem(),
        );
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->name;
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
    public function getValue(): mixed
    {
        return null;
    }
}
