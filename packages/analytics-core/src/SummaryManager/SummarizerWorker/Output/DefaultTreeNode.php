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
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model\MeasureDescription;
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
        private readonly int|float|null $rawValue,
        private readonly string|TranslatableInterface $legend,
        private readonly mixed $member,
        private readonly bool $leaf,
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
        string|TranslatableInterface $legend,
        mixed $member,
    ): self {
        return new self(
            key: $key,
            legend: $legend,
            member: $member,
            value: null,
            rawValue: null,
            leaf: false,
        );
    }

    public static function createLeafNode(
        string $key,
        mixed $value,
        int|float|null $rawValue,
        string|TranslatableInterface $legend,
        mixed $member,
    ): self {
        return new self(
            key: $key,
            legend: $legend,
            member: $member,
            value: $value,
            rawValue: $rawValue,
            leaf: true,
        );
    }

    public function isEqual(self $other): bool
    {
        return $this->key === $other->key
            && $this->member === $other->member;
        ;
    }

    #[\Override]
    public function isLeaf(): bool
    {
        return $this->leaf;
    }

    #[\Override]
    public function getLegend(): string|TranslatableInterface
    {
        return $this->legend;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->member;
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
    public function getRawValue(): int|float|null
    {
        return $this->rawValue;
    }

    #[\Override]
    public function getMeasurePropertyName(): ?string
    {
        if ($this->member instanceof MeasureDescription) {
            return $this->member->getMeasurePropertyName();
        }

        return null;
    }
}
