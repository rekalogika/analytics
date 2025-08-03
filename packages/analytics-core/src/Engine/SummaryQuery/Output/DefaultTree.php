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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Engine\SummaryQuery\Exception\DimensionNamesException;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\TreeContext;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTree>
 * @internal
 */
final class DefaultTree implements TreeNode, \IteratorAggregate
{
    private ?DefaultMeasure $measure = null;

    private ?bool $isNull = null;

    /**
     * @var array<string,DefaultTreeNodes>
     */
    private array $children = [];

    /**
     * @param list<string> $measureNames
     */
    public function __construct(
        private readonly DefaultTuple $tuple,
        private readonly DimensionNames $descendantdimensionNames,
        private readonly array $measureNames,
        private readonly ?TranslatableInterface $rootLabel,
        private readonly TreeContext $context,
    ) {}

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
        DefaultTable $table,
        DefaultNormalTable $normalTable,
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
            table: $table,
            resultContext: $normalTable->getContext(),
            nodesLimit: $nodesLimit,
        );

        $descendantdimensionNames = new DimensionNames($dimensionNames);

        return new self(
            descendantdimensionNames: $descendantdimensionNames,
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
    public function getDimensionNames(): array
    {
        return $this->descendantdimensionNames->toArray();
    }

    #[\Override]
    public function getMeasure(): DefaultMeasure
    {
        if (\count($this->measureNames) !== 1) {
            return $this->context
                ->getNullMeasureCollection()
                ->getNullMeasure($this->measureNames[0]);
        }

        if ($this->measure !== null) {
            return $this->measure;
        }

        $measure = $this->context
            ->getTable()
            ->getMeasureByTuple($this->tuple);

        if ($measure === null) {
            $measure = $this->context
                ->getNullMeasureCollection()
                ->getNullMeasure($this->measureNames[0]);
        }

        return $this->measure = $measure;
    }

    #[\Override]
    public function getMeasures(): Measures
    {
        if (!$this->descendantdimensionNames->hasMeasureDimension()) {
            $measure = $this->getMeasure();

            return new DefaultMeasures([$measure]);
        }

        $measures = [];
        $subtotalChildren = $this->getChildren('@values');

        foreach ($subtotalChildren as $subtotalChild) {
            $measure = $subtotalChild->getMeasure();

            $measures[] = $measure;
        }

        return new DefaultMeasures($measures);
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->isNull ??= (
            ($this->tuple->last()?->isInterpolation() ?? false)
            || !$this->context->getTable()->hasKey($this->tuple->withoutMeasure())
        );
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
        return $this->getChildren()->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->getChildren()->getIterator();
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultTree
    {
        return $this->getChildren()->getByKey($key);
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultTree
    {
        return $this->getChildren()->getByIndex($index);
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        return $this->getChildren()->hasKey($key);
    }

    #[\Override]
    public function first(): ?DefaultTree
    {
        return $this->getChildren()->first();
    }

    #[\Override]
    public function last(): ?DefaultTree
    {
        return $this->getChildren()->last();
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
    public function getChildren(int|string $name = 1): DefaultTreeNodes
    {
        try {
            return $this->getChildrenOrFail($name);
        } catch (DimensionNamesException) {
            return new DefaultTreeNodes([]);
        }
    }

    /**
     * @param int<1,max>|int<min,-1>|string $name
     */
    private function getChildrenOrFail(int|string $name = 1): DefaultTreeNodes
    {
        $name = $this->descendantdimensionNames->resolveName($name);

        if (isset($this->children[$name])) {
            return $this->children[$name];
        }

        return $this->children[$name] =
            new DefaultTreeNodes($this->getBalancedChildren($name));
    }

    /**
     * @return list<DefaultTree>
     */
    private function getBalancedChildren(string $name): array
    {
        $descendantdimensionNames = $this->descendantdimensionNames;

        if (!$descendantdimensionNames->hasName($name)) {
            throw new InvalidArgumentException(\sprintf(
                'Dimension "%s" is not in the descendant dimension names: %s.',
                $name,
                (string) $descendantdimensionNames,
            ));
        }

        $descendantdimensionNames = $descendantdimensionNames->removeUpTo($name);

        $dimensions = $this->context
            ->getDimensionCollection()
            ->getDimensionsByName($name)
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

        return $balancedChildren;
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

    public function getContext(): ResultContext
    {
        return $this->context->getResultContext();
    }
}
