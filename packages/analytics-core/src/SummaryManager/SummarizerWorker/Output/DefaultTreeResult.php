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

use Rekalogika\Analytics\Query\TreeNode;
use Rekalogika\Analytics\Query\TreeResult;

/**
 * @implements \IteratorAggregate<mixed,TreeNode>
 * @internal
 */
final readonly class DefaultTreeResult implements TreeResult, \IteratorAggregate
{
    use NodeTrait;

    /**
     * @param class-string $summaryClass
     * @param list<TreeNode> $children
     */
    public function __construct(
        private string $summaryClass,
        private array $children,
    ) {}

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->children as $child) {
            yield $child->getMember() => $child;
        }
    }
}
