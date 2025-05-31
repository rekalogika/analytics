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

use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final class DefaultTreeNode implements TreeNode, \IteratorAggregate
{
    use NodeTrait;
    use BalancedTreeChildrenTrait;

    /**
     * @var list<DefaultTreeNode>
     */
    private array $children = [];

    private DefaultTuple $tuple;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly ?string $childrenKey,
        private readonly null|DefaultTreeNode $parent,
        private readonly DefaultDimension $dimension,
        private ?DefaultMeasure $measure,
        private readonly Items $items,
        private readonly bool $null,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
    ) {
        $parent?->addChild($this);

        if ($parent !== null) {
            $this->tuple = $parent->getTuple()->append($this->dimension);
        } else {
            $this->tuple = new DefaultTuple(
                summaryTable: $this->summaryClass,
                dimensions: [$this->dimension],
            );
        }
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getBalancedChildren());
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->getBalancedChildren() as $child) {
            yield $child->getMember() => $child;
        }
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

        $parent = $this;

        do {
            /** @psalm-suppress MixedAssignment */
            $member = $parent->getMember();

            if ($member instanceof DefaultMeasureMember) {
                $measureMember = $member;
                break;
            }
        } while ($parent = $parent->getParent());

        if ($measureMember === null) {
            throw new UnexpectedValueException('Measure member not found');
        }

        // get the measure key
        $measureProperty = $measureMember->getMeasureProperty();

        // get the null measure
        $measure = $this->items->getMeasure($measureProperty);

        return $this->measure = $measure;
    }

    public function getParent(): null|DefaultTreeNode
    {
        return $this->parent;
    }

    public function __clone()
    {
        $this->children = [];
    }

    private function addChild(DefaultTreeNode $node): void
    {
        if ($this->childrenKey === null) {
            throw new LogicException('Cannot add child to a leaf node');
        }

        if ($node->getKey() !== $this->childrenKey) {
            throw new UnexpectedValueException(
                \sprintf('Invalid child key "%s", expected "%s"', $node->getKey(), $this->childrenKey),
            );
        }

        $this->children[] = $node;
    }

    public function getChildrenKey(): ?string
    {
        return $this->childrenKey;
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
