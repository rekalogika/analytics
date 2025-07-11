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

namespace Rekalogika\Analytics\Engine\Entity;

/**
 * @implements \IteratorAggregate<DirtyPartition>
 */
final readonly class DirtyPartitionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param class-string $summaryClass
     * @param list<DirtyPartition> $dirtyPartitions
     */
    public function __construct(
        private string $summaryClass,
        private array $dirtyPartitions,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->dirtyPartitions);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dirtyPartitions);
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }
}
