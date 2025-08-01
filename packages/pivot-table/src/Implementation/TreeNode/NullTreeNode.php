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
use Rekalogika\PivotTable\Contracts\Tree\TreeNode;

final readonly class NullTreeNode implements TreeNode
{
    /**
     * @var iterable<NullSubtotalNode> $subtotals
     */
    private iterable $subtotals;

    /**
     * @param iterable<SubtotalNode> $subtotals
     */
    public function __construct(
        private string $name,
        private mixed $legend,
        private mixed $item,
        iterable $subtotals,
        private bool $isLeaf,
    ) {
        $newSubtotals = [];

        foreach ($subtotals as $subtotal) {
            $newSubtotals[] = new NullSubtotalNode(
                name: $subtotal->getKey(),
                legend: $subtotal->getLegend(),
                item: $subtotal->getItem(),
            );
        }

        $this->subtotals = $newSubtotals;
    }

    public static function fromInterface(TreeNode $node): self
    {
        return new self(
            name: $node->getKey(),
            legend: $node->getLegend(),
            item: $node->getItem(),
            subtotals: $node->getSubtotals(),
            isLeaf: $node->isLeaf(),
        );
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->isLeaf;
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
    public function getChildren(int $level = 1): NullTreeNodes
    {
        return new NullTreeNodes([]);
    }

    #[\Override]
    public function getSubtotals(): iterable
    {
        return $this->subtotals;
    }
}
