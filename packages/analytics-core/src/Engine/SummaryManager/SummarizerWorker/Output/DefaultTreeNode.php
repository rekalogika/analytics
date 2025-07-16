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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;
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
    private DefaultMeasures $subtotals;

    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly ?string $childrenKey,
        private readonly null|DefaultTreeNode $parent,
        private readonly DefaultDimension $dimension,
        private ?DefaultMeasure $measure,
        private readonly ItemCollection $itemCollection,
        private readonly bool $null,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
        RowCollection $rowCollection,
    ) {
        $parent?->addChild($this);

        if ($parent !== null) {
            $this->tuple = $parent->getTuple()->append($this->dimension);
        } else {
            $this->tuple = new DefaultTuple(
                summaryClass: $this->summaryClass,
                dimensions: [$this->dimension],
            );
        }

        $this->subtotals = $rowCollection->getMeasures($this->tuple);
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
        return $this->getName() === $other->getName()
            && $this->getRawMember() === $other->getRawMember();
    }

    #[\Override]
    public function getName(): string
    {
        return $this->dimension->getName();
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
        $measure = $this->itemCollection->getMeasure($measureProperty);

        return $this->measure = $measure;
    }

    #[\Override]
    public function getSubtotals(): Measures
    {
        return $this->subtotals;
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

        if ($node->getName() !== $this->childrenKey) {
            throw new UnexpectedValueException(
                \sprintf('Invalid child key "%s", expected "%s"', $node->getName(), $this->childrenKey),
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
