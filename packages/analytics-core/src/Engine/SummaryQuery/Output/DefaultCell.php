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
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Engine\SummaryQuery\Helper\ResultContext;

final class DefaultCell implements CubeCell
{
    private ?DefaultMeasure $measure = null;

    /**
     * @var array<string,DefaultCells>
     */
    private array $drillDowns = [];

    /**
     * @param list<string> $measureNames
     */
    public function __construct(
        private readonly DefaultTuple $tuple,
        private readonly DefaultMeasures $measures,
        private readonly array $measureNames,
        private readonly bool $isNull,
        private readonly ResultContext $context,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->tuple->getSummaryClass();
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->isNull;
    }

    #[\Override]
    public function getApex(): self
    {
        return $this->context->getCellRepository()->getApexCell();
    }

    /**
     * @return list<string>
     */
    public function getMeasureNames(): array
    {
        return $this->measureNames;
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    #[\Override]
    public function getMeasure(): DefaultMeasure
    {
        if ($this->measure !== null) {
            return $this->measure;
        }

        $measureName = $this->tuple->getMeasureName();

        // does not have @values in the tuple
        if ($measureName === null) {
            $measure = DefaultMeasure::createMultiple();
        } else {
            $measure = $this->measures->getByKey($measureName)
                ?? throw new InvalidArgumentException(
                    \sprintf('Measure with name "%s" does not exist.', $measureName),
                );
        }

        return $this->measure = $measure;
    }

    #[\Override]
    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }

    /**
     * @return list<string>
     */
    public function getDimensionality(): array
    {
        return $this->tuple->getDimensionality();
    }

    #[\Override]
    public function rollUp(string $dimensionName): DefaultCell
    {
        $rolledUpTuple = $this->tuple->without($dimensionName);

        return $this->context
            ->getCellRepository()
            ->getCellByTuple($rolledUpTuple)
            ?? throw new UnexpectedValueException('Roll-up cell must always exists.');
    }

    #[\Override]
    public function drillDown(string $dimensionName): DefaultCells
    {
        return $this->drillDowns[$dimensionName] ??= new DefaultCells(
            baseCell: $this,
            childDimensionName: $dimensionName,
            context: $this->context,
        );
    }

    public function getContext(): ResultContext
    {
        return $this->context;
    }

    // private function canDescribeThisNode(mixed $input): bool
    // {
    //     /** @psalm-suppress MixedAssignment */
    //     $member = $this->getMember();

    //     if (
    //         $member instanceof MeasureMember
    //         && $member->getMeasureProperty() === $input
    //     ) {
    //         return true;
    //     }

    //     if ($member === $input) {
    //         return true;
    //     }

    //     if (
    //         $member instanceof \Stringable
    //         && $member->__toString() === $input
    //     ) {
    //         return true;
    //     }

    //     return false;
    // }

    // private function getChildByDescription(mixed $input): ?DefaultTree
    // {
    //     foreach ($this as $child) {
    //         if ($child->canDescribeThisNode($input)) {
    //             return $child;
    //         }
    //     }

    //     return null;
    // }

    // #[\Override]
    // public function traverse(mixed ...$members): ?DefaultTree
    // {
    //     if ($members === []) {
    //         throw new InvalidArgumentException(
    //             'Cannot traverse to empty members, expected at least 1 member.',
    //         );
    //     }

    //     /** @psalm-suppress MixedAssignment */
    //     $first = array_shift($members);

    //     $child = $this->getChildByDescription($first);

    //     if ($child === null) {
    //         return null;
    //     }

    //     if ($members === []) {
    //         return $child;
    //     }

    //     return $child->traverse(...$members);
    // }
}
