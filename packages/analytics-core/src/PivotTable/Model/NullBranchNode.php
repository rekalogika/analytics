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

use Rekalogika\Analytics\PivotTable\BranchNode;

final readonly class NullBranchNode implements BranchNode
{
    public function __construct(
        private string $key,
        private mixed $legend,
        private mixed $item,
    ) {}

    public static function fromInterface(BranchNode $branchNode): self
    {
        return new self(
            key: $branchNode->getKey(),
            legend: $branchNode->getLegend(),
            item: $branchNode->getItem(),
        );
    }

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
}
