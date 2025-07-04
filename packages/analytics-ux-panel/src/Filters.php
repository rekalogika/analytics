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

namespace Rekalogika\Analytics\UX\PanelBundle;

/**
 * @implements \IteratorAggregate<string,Filter>
 */
final readonly class Filters implements \IteratorAggregate
{
    /**
     * @param array<string,Filter> $filters
     */
    public function __construct(private array $filters) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }
}
