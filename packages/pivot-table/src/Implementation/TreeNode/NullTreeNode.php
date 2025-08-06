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

use Rekalogika\PivotTable\Contracts\TreeNode;

final readonly class NullTreeNode implements TreeNode
{
    /**
     * @param list<string> $path
     */
    public function __construct(
        private array $path,
        private string $name,
        private mixed $legend,
        private mixed $item,
        private bool $isLeaf,
    ) {}

    public static function fromInterface(TreeNode $node): self
    {
        return new self(
            path: $node->getPath(),
            name: $node->getKey(),
            legend: $node->getLegend(),
            item: $node->getItem(),
            isLeaf: $node->isLeaf(),
        );
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->isLeaf;
    }

    #[\Override]
    public function getPath(): array
    {
        return $this->path;
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

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        yield from [];
    }

    #[\Override]
    public function rollUp(array $keys): TreeNode
    {
        return $this;
    }
}
