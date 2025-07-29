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

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\ItemCollector\ItemCollection;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final class DefaultTree implements TreeNode, \IteratorAggregate
{
    use NodeTrait;
    use BalancedTreeChildrenTrait;

    private DefaultTuple $tuple;
    private DefaultMeasures $subtotals;

    /**
     * @param class-string $summaryClass
     * @param list<DefaultTreeNode> $children
     */
    public function __construct(
        private readonly string $summaryClass,
        private readonly TranslatableInterface $label,
        private readonly ?string $childrenKey,
        private readonly array $children,
        private readonly ItemCollection $itemCollection,
        private readonly DefaultTreeNodeFactory $treeNodeFactory,
        private readonly RowCollection $rowCollection,
        ?Expression $condition,
    ) {
        if ($childrenKey === null && $children !== []) {
            throw new UnexpectedValueException('Children key cannot be null if children is not empty');
        }

        foreach ($children as $child) {
            if ($child->getName() !== $childrenKey) {
                throw new UnexpectedValueException(
                    \sprintf('Invalid child key "%s", expected "%s"', $child->getName(), (string) $childrenKey),
                );
            }
        }

        $this->tuple = new DefaultTuple(
            summaryClass: $summaryClass,
            dimensions: [],
            condition: $condition,
        );

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
    public function getMeasure(): ?DefaultMeasure
    {
        return null;
    }

    #[\Override]
    public function getSubtotals(): Measures
    {
        return $this->subtotals;
    }

    #[\Override]
    public function isNull(): bool
    {
        return false;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return null;
    }

    #[\Override]
    public function getName(): string
    {
        return '';
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

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

    public function getItemCollection(): ItemCollection
    {
        return $this->itemCollection;
    }

    public function getChildrenKey(): ?string
    {
        return $this->childrenKey;
    }

    public function getRowCollection(): RowCollection
    {
        return $this->rowCollection;
    }
}
