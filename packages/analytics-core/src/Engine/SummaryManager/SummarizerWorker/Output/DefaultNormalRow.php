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
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\NormalRow;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Engine\Util\DimensionUtil;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final readonly class DefaultNormalRow implements NormalRow, \IteratorAggregate
{
    public function __construct(
        private DefaultTuple $tuple,
        private DefaultMeasure $measure,
        private ?GroupingField $groupings,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->tuple->getSummaryClass();
    }

    #[\Override]
    public function getMeasure(): DefaultMeasure
    {
        return $this->measure;
    }

    public function getGroupings(): ?GroupingField
    {
        return $this->groupings;
    }

    /**
     * @param array<string,int<0,max>> $measures
     * @return -1|0|1
     */
    public static function compare(self $row1, self $row2, array $measures): int
    {
        foreach ($row1 as $name => $value1) {
            $value2 = $row2->getByName($name);

            if ($value2 === null) {
                return 1;
            }

            if ($name === '@values') {
                $measure1Order = $measures[$row1->getMeasure()->getName()]
                    ?? throw new LogicException('Measure not found');

                $measure2Order = $measures[$row2->getMeasure()->getName()]
                    ?? throw new LogicException('Measure not found');

                $comparison = $measure1Order <=> $measure2Order;

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            if (!DimensionUtil::isDimensionSame($value1, $value2)) {
                return 0;
            }
        }

        return 0;
    }

    public function hasSameDimensions(self $other): bool
    {
        return $this->tuple->isSame($other->tuple);
    }

    public function getWithoutValues(): DefaultTuple
    {
        return $this->tuple->getWithoutValues();
    }

    #[\Override]
    public function getByName(string $name): ?Dimension
    {
        return $this->tuple->getByName($name);
    }

    #[\Override]
    public function getByIndex(int $index): ?Dimension
    {
        return $this->tuple->getByIndex($index);
    }

    #[\Override]
    public function has(string $name): bool
    {
        return $this->tuple->has($name);
    }

    #[\Override]
    public function getMembers(): array
    {
        return $this->tuple->getMembers();
    }

    #[\Override]
    public function isSame(Tuple $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->tuple->isSame($other->tuple);
    }

    #[\Override]
    public function count(): int
    {
        return $this->tuple->count();
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->tuple->getIterator();
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }

    public function isSubtotal(): bool
    {
        return $this->groupings?->isSubtotal() ?? false;
    }
}
