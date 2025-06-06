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

use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Tuple;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final readonly class DefaultRow implements Row, \IteratorAggregate
{
    /**
     * @param class-string $summaryClass
     */
    public function __construct(
        private string $summaryClass,
        private DefaultTuple $tuple,
        private DefaultMeasures $measures,
        private string $groupings,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return $this->tuple->getIterator();
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getMeasures(): DefaultMeasures
    {
        return $this->measures;
    }

    public function getGroupings(): string
    {
        return $this->groupings;
    }

    public function isSubtotal(): bool
    {
        return substr_count($this->groupings, '1') !== 0;
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
        return $this->tuple->isSame($other);
    }

    #[\Override]
    public function count(): int
    {
        return $this->tuple->count();
    }
}
