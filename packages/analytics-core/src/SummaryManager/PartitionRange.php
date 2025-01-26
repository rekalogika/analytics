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

namespace Rekalogika\Analytics\SummaryManager;

use Rekalogika\Analytics\Partition;
use Rekalogika\Analytics\Util\PartitionUtil;

/**
 * @implements \IteratorAggregate<Partition>
 */
class PartitionRange implements \IteratorAggregate, \Countable
{
    public function __construct(
        private readonly Partition $start,
        private readonly Partition $end,
    ) {
        if ($start->getLevel() !== $end->getLevel()) {
            throw new \InvalidArgumentException('The start and end partitions must have the same level.');
        }
    }

    public function getSignature(): string
    {
        return hash('xxh128', serialize($this));
    }

    /**
     * @return iterable<self>
     */
    public function batch(int $batchSize): iterable
    {
        $batches = [];
        $i = 0;

        foreach ($this as $partition) {
            $batches[] = $partition;
            $i++;

            if ($i === $batchSize) {
                yield new self($batches[0], $batches[\count($batches) - 1]);
                $batches = [];
                $i = 0;
            }
        }

        if ($batches !== []) {
            yield new self($batches[0], $batches[\count($batches) - 1]);
        }
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        $current = $this->start;

        while ($current !== null && !PartitionUtil::isGreaterThan($current, $this->end)) {
            yield $current;

            $current = $current->getNext();
        }
    }

    #[\Override]
    public function count(): int
    {
        $count = 0;
        foreach ($this as $_) {
            $count++;
        }

        return $count;
    }

    public function getStart(): Partition
    {
        return $this->start;
    }

    public function getEnd(): Partition
    {
        return $this->end;
    }

    public function getLowerBound(): mixed
    {
        return $this->start->getLowerBound();
    }

    public function getUpperBound(): mixed
    {
        return $this->end->getUpperBound();
    }

    public function getLevel(): int
    {
        return $this->start->getLevel();
    }

    public function getContainingRange(): ?PartitionRange
    {
        $start = $this->start->getContaining();
        $end = $this->end->getContaining();

        if ($start === null || $end === null) {
            return null;
        }

        return new self($start, $end);
    }

    public function getRangeAboveForRefresh(): ?PartitionRange
    {
        $start = $this->start->getContaining();
        $end = $this->end->getContaining();

        if ($start === null || $end === null) {
            return null;
        }

        return new self($start, $end);
    }
}
