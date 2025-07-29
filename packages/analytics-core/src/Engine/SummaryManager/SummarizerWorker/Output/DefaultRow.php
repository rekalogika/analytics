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
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Tuple;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final readonly class DefaultRow implements Row, \IteratorAggregate
{
    public function __construct(
        private DefaultTuple $tuple,
        private DefaultMeasures $measures,
        private ?GroupingField $groupings,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->tuple->getIterator();
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->tuple->getSummaryClass();
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    public function getGroupings(): ?GroupingField
    {
        return $this->groupings;
    }

    public function isGrouping(): bool
    {
        return $this->groupings?->isSubtotal() ?? false;
    }

    #[\Override]
    public function getByKey(mixed $key): mixed
    {
        return $this->tuple->getByKey($key);
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultDimension
    {
        return $this->tuple->getByIndex($index);
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        return $this->tuple->hasKey($key);
    }

    #[\Override]
    public function first(): mixed
    {
        return $this->tuple->first();
    }

    #[\Override]
    public function last(): mixed
    {
        return $this->tuple->last();
    }

    #[\Override]
    public function getMembers(): array
    {
        return $this->tuple->getMembers();
    }

    #[\Override]
    public function isSame(Tuple $other): bool
    {
        return $this->tuple->isSame($other);
    }

    #[\Override]
    public function count(): int
    {
        return $this->tuple->count();
    }

    #[\Override]
    public function getCondition(): ?Expression
    {
        return $this->tuple->getCondition();
    }

    public function getSignature(): string
    {
        return $this->tuple->getSignature();
    }

    public function getTuple(): DefaultTuple
    {
        return $this->tuple;
    }
}
