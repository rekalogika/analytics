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

namespace Rekalogika\Analytics\SummaryManager\SummarizerWorker\Model;

use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\Query\ResultNode;

/**
 * @implements \IteratorAggregate<mixed,ResultNode>
 */
final readonly class DefaultSummaryResult implements Result, \IteratorAggregate
{
    use NodeTrait;

    /**
     * @param list<ResultNode> $children
     */
    public function __construct(
        private array $children,
    ) {}

    public function getIterator(): \Traversable
    {
        foreach ($this->children as $child) {
            yield $child->getItem() => $child;
        }
    }
}
