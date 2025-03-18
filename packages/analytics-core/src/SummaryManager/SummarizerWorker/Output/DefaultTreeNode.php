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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Query\TreeNode;
use Rekalogika\Analytics\Query\Unit;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,TreeNode>
 * @internal
 */
final class DefaultTreeNode implements TreeNode, \IteratorAggregate
{
    use NodeTrait;

    /**
     * @var list<DefaultTreeNode>
     */
    private array $children = [];

    private ?DefaultTreeNode $parent = null;

    private function __construct(
        private readonly string $key,
        private readonly mixed $value,
        private readonly mixed $rawValue,
        private readonly int|float $numericValue,
        private readonly string|TranslatableInterface $label,
        private readonly mixed $member,
        private readonly mixed $rawMember,
        private readonly mixed $displayMember,
        private readonly bool $leaf,
        private readonly ?Unit $unit,
    ) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->children as $child) {
            yield $child->getMember() => $child;
        }
    }

    public static function createBranchNode(
        string $key,
        string|TranslatableInterface $label,
        mixed $member,
        mixed $rawMember,
        mixed $displayMember,
    ): self {
        return new self(
            key: $key,
            label: $label,
            member: $member,
            rawMember: $rawMember,
            displayMember: $displayMember,
            value: null,
            rawValue: null,
            numericValue: 0,
            unit: null,
            leaf: false,
        );
    }

    public static function createLeafNode(
        string $key,
        mixed $value,
        mixed $rawValue,
        int|float $numericValue,
        ?Unit $unit,
        string|TranslatableInterface $label,
        mixed $member,
        mixed $rawMember,
        mixed $displayMember,
    ): self {
        return new self(
            key: $key,
            label: $label,
            member: $member,
            rawMember: $rawMember,
            displayMember: $displayMember,
            value: $value,
            rawValue: $rawValue,
            numericValue: $numericValue,
            unit: $unit,
            leaf: true,
        );
    }

    public function isEqual(self $other): bool
    {
        return $this->key === $other->key
            && $this->member === $other->member;
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->leaf;
    }

    #[\Override]
    public function getLabel(): string|TranslatableInterface
    {
        return $this->label;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->member;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->rawMember;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->displayMember;
    }

    public function setParent(DefaultTreeNode $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?DefaultTreeNode
    {
        return $this->parent;
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->key;
    }

    public function __clone()
    {
        $this->children = [];
    }

    public function addChild(DefaultTreeNode $node): void
    {
        $this->children[] = $node;
        $node->setParent($this);
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }

    #[\Override]
    public function getNumericValue(): int|float
    {
        return $this->numericValue;
    }

    #[\Override]
    public function getUnit(): ?Unit
    {
        return $this->unit;
    }
}
