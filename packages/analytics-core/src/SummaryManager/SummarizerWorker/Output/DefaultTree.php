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

use Rekalogika\Analytics\Contracts\Result\Tree;
use Rekalogika\Analytics\Exception\UnexpectedValueException;
use Rekalogika\Analytics\SummaryManager\SummarizerWorker\ItemCollector\Items;

/**
 * @implements \IteratorAggregate<mixed,DefaultTreeNode>
 * @internal
 */
final readonly class DefaultTree implements Tree, \IteratorAggregate
{
    use NodeTrait;

    /**
     * @param class-string $summaryClass
     * @param list<DefaultTreeNode> $children
     */
    public function __construct(
        private string $summaryClass,
        private ?string $childrenKey,
        private array $children,
        private Items $uniqueDimensions,
    ) {
        if ($childrenKey === null) {
            if ($children !== []) {
                throw new UnexpectedValueException('Children key cannot be null if children is not empty');
            }
        }

        foreach ($children as $child) {
            if ($child->getKey() !== $childrenKey) {
                throw new UnexpectedValueException(
                    \sprintf('Invalid child key "%s", expected "%s"', $child->getKey(), get_debug_type($childrenKey)),
                );
            }
        }
    }

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

    public function getUniqueDimensions(): Items
    {
        return $this->uniqueDimensions;
    }

    public function getChildrenKey(): ?string
    {
        return $this->childrenKey;
    }
}
