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

final class SummaryItem
{
    /**
     * @var list<SummaryItem>
     */
    private array $children = [];

    private ?SummaryItem $parent = null;

    private function __construct(
        private readonly string $key,
        private readonly mixed $value,
        private readonly int|float|null $rawValue,
        private readonly mixed $legend,
        private readonly mixed $item,
        private readonly bool $leaf,
    ) {}

    public static function createBranchItem(
        string $key,
        mixed $legend,
        mixed $item,
    ): self {
        return new self(
            key: $key,
            legend: $legend,
            item: $item,
            value: null,
            rawValue: null,
            leaf: false,
        );
    }

    public static function createLeafItem(
        string $key,
        mixed $value,
        int|float|null $rawValue,
        mixed $legend,
        mixed $item,
    ): self {
        return new self(
            key: $key,
            legend: $legend,
            item: $item,
            value: $value,
            rawValue: $rawValue,
            leaf: true,
        );
    }

    public function isEqual(self $other): bool
    {
        return $this->key === $other->key
            && $this->item === $other->item;
        ;
    }

    public function isLeaf(): bool
    {
        return $this->leaf;
    }

    public function getLegend(): mixed
    {
        return $this->legend;
    }

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

    public function getKey(): string
    {
        return $this->key;
    }

    public function __clone()
    {
        $this->children = [];
    }

    public function addChild(SummaryItem $item): void
    {
        $this->children[] = $item;
        $item->setParent($this);
    }

    /**
     * @return list<SummaryItem>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getRawValue(): int|float|null
    {
        return $this->rawValue;
    }
}
