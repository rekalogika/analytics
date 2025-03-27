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

use Rekalogika\Analytics\Contracts\Measure;
use Rekalogika\Analytics\Contracts\TreeNode;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\DimensionCollector\UniqueDimensions;
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
        private readonly ?string $childrenKey,
        private readonly DefaultDimension $dimension,
        private readonly ?Measure $measure,
        private readonly UniqueDimensions $uniqueDimensions,
    ) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getChildren() as $child) {
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
        );
    }

    public static function createLeafNode(
        DefaultDimension $dimension,
        UniqueDimensions $uniqueDimensions,
        Measure $measure,
    ): self {
        return new self(
            childrenKey: null,
            dimension: $dimension,
            uniqueDimensions: $uniqueDimensions,
            measure: $measure,
        );
    }

    public function isEqual(self $other): bool
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
    public function getMeasure(): ?Measure
    {
        return $this->measure;
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
}
