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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector;

use Rekalogika\Analytics\SummaryManager\SummarizerWorker\Output\DefaultDimension;

/**
 * @implements \IteratorAggregate<DefaultDimension>
 */
final readonly class DimensionColletion implements \IteratorAggregate, \Countable
{
    /**
     * @param list<DefaultDimension> $dimensions
     */
    public function __construct(
        private string $key,
        private array $dimensions,
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }
}
