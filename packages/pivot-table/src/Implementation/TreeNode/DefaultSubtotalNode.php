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

use Rekalogika\PivotTable\Contracts\Tree\SubtotalNode;

final readonly class DefaultSubtotalNode implements SubtotalNode
{
    public function __construct(
        private string $name,
        private mixed $legend,
        private mixed $item,
        private mixed $value,
    ) {}

    public static function fromInterface(SubtotalNode $leafNode): self
    {
        return new self(
            name: $leafNode->getKey(),
            legend: $leafNode->getLegend(),
            item: $leafNode->getItem(),
            value: $leafNode->getValue(),
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
        return $this->value;
    }
}
