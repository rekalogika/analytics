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

abstract class SummaryField
{
    /**
     * @var list<SummaryField>
     */
    private array $children = [];

    private ?SummaryField $parent = null;

    protected function __construct(
        private readonly string $key,
        private readonly mixed $legend,
        private readonly mixed $item,
        private readonly mixed $value,
        private readonly int|float|null $rawValue,
    ) {}

    public function isEqual(self $other): bool
    {
        return $this->key === $other->key
            && $this->item === $other->item;
        ;
    }

    public function getLegend(): mixed
    {
        return $this->legend;
    }

    public function getItem(): mixed
    {
        return $this->item;
    }

    public function setParent(SummaryField $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?SummaryField
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

    public function addChild(SummaryField $item): void
    {
        $this->children[] = $item;
        $item->setParent($this);
    }

    /**
     * @return list<SummaryField>
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
