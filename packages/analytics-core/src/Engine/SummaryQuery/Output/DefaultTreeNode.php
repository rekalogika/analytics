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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Contracts\Result\TreeNode;
use Rekalogika\Analytics\Contracts\Translation\LiteralString;
use Rekalogika\Analytics\Engine\SummaryQuery\Registry\TreeNodeRegistry;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 */
final class DefaultTreeNode implements TreeNode, \IteratorAggregate
{
    use MeasuresTrait;

    /**
     * @param list<string> $dimensionality
     */
    public static function createRoot(
        DefaultCell $cell,
        array $dimensionality,
    ): self {
        $registry = new TreeNodeRegistry();

        return new self(
            cell: $cell,
            dimensionality: Dimensionality::create($dimensionality),
            registry: $registry,
        );
    }

    public function __construct(
        private readonly DefaultCell $cell,
        private readonly Dimensionality $dimensionality,
        private readonly TreeNodeRegistry $registry,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->cell->getSummaryClass();
    }

    #[\Override]
    public function getTuple(): DefaultOrderedTuple
    {
        return $this->cell->getTuple()
            ->withOrder($this->dimensionality->getAncestorsToCurrent());
    }

    #[\Override]
    public function getChildren(int|string $name = 1): DefaultTreeNodes
    {
        $name = $this->dimensionality->resolveName($name);
        $dimensionality = $this->dimensionality->descend($name);
        $cells = $this->cell->drillDown($name);

        return new DefaultTreeNodes(
            cells: $cells,
            dimensionality: $dimensionality,
            registry: $this->registry,
        );
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->cell->getMeasures();
    }

    private function getDimension(): ?DefaultDimension
    {
        $dimensionName = $this->dimensionality->getCurrent();

        if ($dimensionName === null) {
            return null;
        }

        return $this->cell->getTuple()->get($dimensionName);
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->cell->isNull();
    }

    #[\Override]
    public function get(mixed $key): mixed
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->getChildren() as $childKey => $child) {
            if ($childKey === $key) {
                return $child;
            }
        }

        return null;
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $i = 0;

        /** @psalm-suppress MixedAssignment */
        foreach ($this->getChildren() as $child) {
            if ($i === $index) {
                return $child;
            }
            $i++;
        }

        return null;
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->getChildren() as $childKey => $child) {
            if ($childKey === $key) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function first(): mixed
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($this->getChildren() as $child) {
            return $child;
        }

        return null;
    }

    #[\Override]
    public function last(): mixed
    {
        $last = null;

        /** @psalm-suppress MixedAssignment */
        foreach ($this->getChildren() as $child) {
            $last = $child;
        }

        return $last;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->getChildren());
    }

    #[\Override]
    public function getMember(): mixed
    {
        return $this->getDimension()?->getMember();
    }

    #[\Override]
    public function getRawMember(): mixed
    {
        return $this->getDimension()?->getRawMember();
    }

    #[\Override]
    public function getDisplayMember(): mixed
    {
        return $this->getDimension()?->getDisplayMember();
    }

    #[\Override]
    public function getName(): string
    {
        return $this->getDimension()?->getName() ?? '';
    }

    #[\Override]
    public function getLabel(): TranslatableInterface
    {
        return $this->getDimension()?->getLabel()
            ?? new LiteralString('-');
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->getChildren()->getIterator();
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

    private function getChildByDescription(mixed $input): ?DefaultTreeNode
    {
        foreach ($this as $child) {
            if ($child->canDescribeThisNode($input)) {
                return $child;
            }
        }

        return null;
    }

    #[\Override]
    public function traverse(mixed ...$members): ?DefaultTreeNode
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
