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
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Contracts\Result\TreeNodes;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\DimensionCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\DimensionFactory\NullMeasureCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\RowCollection;
use Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Helper\TreeContext;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTree>
 * @internal
 */
final class DefaultTree implements TreeNode, \IteratorAggregate
{
    private readonly DefaultMeasures $subtotals;

    private ?DefaultMeasure $measure = null;

    /**
     * @var list<DefaultTree>|null
     */
    private ?array $balancedChildren = null;

    /**
     * @var list<DefaultTree>|null
     */
    private ?array $unbalancedChildren = null;

    private ?DefaultTreeNodes $children = null;

    /**
     * @param list<string> $descendantdimensionNames
     * @param list<string> $measureNames
     */
    public function __construct(
        private readonly DefaultTuple $tuple,
        private readonly array $descendantdimensionNames,
        private readonly array $measureNames,
        private readonly ?TranslatableInterface $rootLabel,
        private readonly TreeContext $context,
    ) {
        $this->subtotals = $context->getRowCollection()->getMeasures($this->tuple);
    }

    /**
     * @param class-string $summaryClass
     * @param list<string> $dimensionNames
     * @param list<string> $measureNames
     */
    public static function createRoot(
        string $summaryClass,
        array $dimensionNames,
        array $measureNames,
        TranslatableInterface $rootLabel,
        RowCollection $rowCollection,
        DimensionCollection $dimensionCollection,
        NullMeasureCollection $nullMeasureCollection,
        ?Expression $condition,
        int $nodesLimit,
    ): self {
        if (!\in_array('@values', $dimensionNames, true)) {
            $dimensionNames[] = '@values';
        }

        $rootTuple = new DefaultTuple(
            summaryClass: $summaryClass,
            dimensions: [],
            condition: $condition,
        );

        $context = new TreeContext(
            rowCollection: $rowCollection,
            dimensionCollection: $dimensionCollection,
            nullMeasureCollection: $nullMeasureCollection,
            nodesLimit: $nodesLimit,
        );

        return new self(
            descendantdimensionNames: $dimensionNames,
            measureNames: $measureNames,
            tuple: $rootTuple,
            rootLabel: $rootLabel,
            context: $context,
        );
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->tuple->getSummaryClass();
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    #[\Override]
    public function getMeasure(): ?DefaultMeasure
    {
        if (\count($this->measureNames) !== 1) {
            return null;
        }

        if ($this->measure !== null) {
            return $this->measure;
        }

        $measure = $this->context
            ->getRowCollection()
            ->getMeasure($this->tuple);

        if ($measure === null) {
            $measure = $this->context
                ->getNullMeasureCollection()
                ->getNullMeasure($this->measureNames[0]);
        }

        return $this->measure = $measure;
    }

    #[\Override]
    public function getSubtotals(): Measures
    {
        return $this->subtotals;
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->tuple->last()?->isInterpolation() ?? false;
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->tuple->last()?->getMember();
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->tuple->last()?->getRawMember();
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->tuple->last()?->getDisplayMember();
    }

    #[\Override]
    public function getName(): string
    {
        return $this->tuple->last()?->getName() ?? '';
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        if ($this->rootLabel !== null) {
            return $this->rootLabel;
        }

        return $this->tuple->last()?->getLabel()
            ?? throw new UnexpectedValueException(
                'Root label is not set and tuple does not have a label.',
            );
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

    #[\Override]
    public function getByKey(mixed $key): ?DefaultTree
    {
        $balancedChildren = $this->getBalancedChildren();

        foreach ($balancedChildren as $child) {
            if ($child->getMember() === $key) {
                return $child;
            }
        }

        return null;
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultTree
    {
        $balancedChildren = $this->getBalancedChildren();

        if (!isset($balancedChildren[$index])) {
            return null;
        }

        return $balancedChildren[$index];
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        $balancedChildren = $this->getBalancedChildren();

        foreach ($balancedChildren as $child) {
            if ($child->getMember() === $key) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function first(): ?DefaultTree
    {
        $balancedChildren = $this->getBalancedChildren();

        if (empty($balancedChildren)) {
            return null;
        }

        return $balancedChildren[0];
    }

    #[\Override]
    public function last(): ?DefaultTree
    {
        $balancedChildren = $this->getBalancedChildren();

        if (empty($balancedChildren)) {
            return null;
        }

        return end($balancedChildren);
    }

    public function getDimension(): DefaultDimension
    {
        $dimension = $this->tuple->last();

        if (!$dimension instanceof DefaultDimension) {
            throw new UnexpectedValueException(
                'Expected last tuple item to be an instance of DefaultDimension, '
                    . 'got: ' . get_debug_type($dimension),
            );
        }

        return $dimension;
    }

    #[\Override]
    public function getChildren(): TreeNodes
    {
        if ($this->children !== null) {
            return $this->children;
        }

        return $this->children = new DefaultTreeNodes($this->getBalancedChildren());
    }

    /**
     * @return list<DefaultTree>
     */
    private function getBalancedChildren(): array
    {
        if ($this->balancedChildren !== null) {
            return $this->balancedChildren;
        }

        $descendantdimensionNames = $this->descendantdimensionNames;
        $dimensionName = array_shift($descendantdimensionNames);

        if ($dimensionName === null) {
            return $this->balancedChildren = [];
        }

        $dimensions = $this->context
            ->getDimensionCollection()
            ->getDimensionsByName($dimensionName)
            ->getGapFilled();

        $treeNodeFactory = $this->context->getTreeNodeFactory();

        $balancedChildren = [];

        foreach ($dimensions as $dimension) {
            $tuple = $this->tuple->append($dimension);

            // if the member is a measure (i.e. '@values'), narrow the measure
            // names to the measure name specified in the dimension.

            /** @psalm-suppress MixedAssignment */
            $member = $dimension->getMember();

            if ($member instanceof MeasureMember) {
                $measureNames = [$member->getMeasureProperty()];
            } else {
                $measureNames = $this->measureNames;
            }

            $child = $treeNodeFactory->createNode(
                tuple: $tuple,
                descendantdimensionNames: $descendantdimensionNames,
                measureNames: $measureNames,
            );

            $balancedChildren[] = $child;
        }

        return $this->balancedChildren = $balancedChildren;
    }

    /**
     * @return list<DefaultTree>
     */
    public function getUnbalancedChildren(): array
    {
        if ($this->unbalancedChildren !== null) {
            return $this->unbalancedChildren;
        }

        $balancedChildren = $this->getBalancedChildren();
        $unbalancedChildren = [];

        foreach ($balancedChildren as $child) {
            if ($child->isNull()) {
                continue;
            }

            $unbalancedChildren[] = $child;
        }

        return $this->unbalancedChildren = $unbalancedChildren;
    }

    private function canDescribeThisNode(mixed $input): bool
    {
        /** @psalm-suppress MixedAssignment */
        $member = $this->getMember();

        if (
            $member instanceof MeasureMember
            && $member->getMeasureProperty() === $input
        ) {
            return true;
        }

        if ($member === $input) {
            return true;
        }

        if (
            $member instanceof \Stringable
            && $member->__toString() === $input
        ) {
            return true;
        }

        return false;
    }

    private function getChildByDescription(mixed $input): ?DefaultTree
    {
        foreach ($this as $child) {
            if ($child->canDescribeThisNode($input)) {
                return $child;
            }
        }

        return null;
    }

    #[\Override]
    public function traverse(mixed ...$members): ?DefaultTree
    {
        if ($members === []) {
            throw new InvalidArgumentException(
                'Cannot traverse to empty members, expected at least 1 member.',
            );
        }

        /** @psalm-suppress MixedAssignment */
        $first = array_shift($members);

        $child = $this->getChildByDescription($first);

        if ($child === null) {
            return null;
        }

        if ($members === []) {
            return $child;
        }

        return $child->traverse(...$members);
    }
}
