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

use Rekalogika\Analytics\Query\Dimension;
use Rekalogika\Analytics\Query\Measure;
use Rekalogika\Analytics\Query\TreeNode;
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
        private Dimension $dimension,
        private ?Measure $measure,
    ) {
    }

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

    public static function createBranchNode(Dimension $dimension): self
    {
        return new self(
            dimension: $dimension,
            measure: null,
        );
    }

    public static function createLeafNode(
        Dimension $dimension,
        Measure $measure,
    ): self {
        return new self(
            dimension: $dimension,
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
    public function getLabel(): string|TranslatableInterface
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
        $this->children[] = $node;
        $node->setParent($this);
    }
}
