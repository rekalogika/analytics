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

use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector\UniqueDimensions;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final class DefaultTreeNode implements TreeNode, \IteratorAggregate
{
    use NodeTrait;

    /**
     * @var list<DefaultTreeNode>
     */
    private array $children = [];

    /**
     * @var null|list<DefaultTreeNode>
     */
    private ?array $balancedChildren = null;

    private ?DefaultTreeNode $parent = null;

    private function __construct(
        private readonly ?string $childrenKey,
        private readonly DefaultDimension $dimension,
        private ?DefaultMeasure $measure,
        private readonly UniqueDimensions $uniqueDimensions,
        private readonly bool $null,
    ) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getBalancedChildren() as $child) {
            yield $child->getMember() => $child;
        }
    }

    /**
     * @return list<DefaultTreeNode>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return list<DefaultTreeNode>
     */
    private function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        if ($this->childrenKey === null) {
            return $this->children;
        }

        $uniqueChildrenDimensions = $this->uniqueDimensions
            ->getDimensions($this->childrenKey);

        $balancedChildren = [];

        foreach ($uniqueChildrenDimensions as $dimension) {
            $child = $this->getChildEqualTo($dimension);

            if ($child === null) {
                // continue;
                $child = new DefaultTreeNode(
                    childrenKey: $this->uniqueDimensions
                        ->getKeyAfter($this->childrenKey),
                    dimension: $dimension,
                    measure: null,
                    uniqueDimensions: $this->uniqueDimensions,
                    null: true,
                );
            }

            $child->setParent($this);

            $balancedChildren[] = $child;
        }

        return $this->balancedChildren = $balancedChildren;
    }

    private function getChildEqualTo(
        DefaultDimension $dimension,
    ): ?DefaultTreeNode {
        foreach ($this->children as $child) {
            if ($child->isEqual($dimension)) {
                return $child;
            }
        }

        return null;
    }

    public static function createBranchNode(
        string $childrenKey,
        DefaultDimension $dimension,
        UniqueDimensions $uniqueDimensions,
    ): self {
        return new self(
            childrenKey: $childrenKey,
            dimension: $dimension,
            measure: null,
            uniqueDimensions: $uniqueDimensions,
            null: false,
        );
    }

    public static function createLeafNode(
        DefaultDimension $dimension,
        UniqueDimensions $uniqueDimensions,
        DefaultMeasure $measure,
    ): self {
        return new self(
            childrenKey: null,
            dimension: $dimension,
            uniqueDimensions: $uniqueDimensions,
            measure: $measure,
            null: false,
        );
    }

    public function isEqual(self|DefaultDimension $other): bool
    {
        return $this->getKey() === $other->getKey()
            && $this->getRawMember() === $other->getRawMember();
    }

    #[\Override]
    public function getKey(): string
    {
        return $this->dimension->getKey();
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->dimension->getLabel();
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->dimension->getMember();
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->dimension->getRawMember();
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->dimension->getDisplayMember();
    }

    #[\Override]
    public function getMeasure(): ?DefaultMeasure
    {
        if ($this->measure !== null) {
            return $this->measure;
        }

        // if has children, then measure must be null
        if (\count($this) > 0) {
            return null;
        }

        // we need to create a null measure here, first find the measure
        // member

        $measureMember = null;

        while ($parent = $this->getParent()) {
            /** @psalm-suppress MixedAssignment */
            $member = $parent->getMember();

            if ($member instanceof DefaultMeasureMember) {
                $measureMember = $member;
                break;
            }
        }

        if ($measureMember === null) {
            throw new \UnexpectedValueException('Measure member not found');
        }

        // get the measure key
        $measureProperty = $measureMember->getMeasureProperty();

        // get the null measure
        $measure = $this->uniqueDimensions->getMeasure($measureProperty);

        return $this->measure = $measure;
    }

    public function setParent(DefaultTreeNode $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?DefaultTreeNode
    {
        return $this->parent;
    }

    public function __clone()
    {
        $this->children = [];
    }

    public function addChild(DefaultTreeNode $node): void
    {
        if ($this->childrenKey === null) {
            throw new \LogicException('Cannot add child to a leaf node');
        }

        if ($node->getKey() !== $this->childrenKey) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid child key "%s", expected "%s"', $node->getKey(), $this->childrenKey),
            );
        }

        $this->children[] = $node;
        $node->setParent($this);
    }

    public function getChildrenKey(): ?string
    {
        return $this->childrenKey;
    }

    public function getUniqueDimensions(): UniqueDimensions
    {
        return $this->uniqueDimensions;
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->null;
    }

    public function getDimension(): DefaultDimension
    {
        return $this->dimension;
    }
}
