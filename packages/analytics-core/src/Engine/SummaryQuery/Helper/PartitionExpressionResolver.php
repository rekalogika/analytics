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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Helper;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Model\Partition;
use Rekalogika\Analytics\Engine\Util\PartitionUtil;

/**
 * Builds the WHERE expression that selects the summary rows a query must read.
 *
 * The summary table stores the same data at multiple partition levels. To
 * answer a query, we need a set of partitions that covers every source value
 * from the beginning up to the last summarized value exactly once, without
 * gaps or overlaps. To minimize the number of rows read, we prefer the
 * highest-level partition available for each part of the range.
 *
 * Example, with partition widths L1 = 2, L2 = 4, L3 = 8, and the last
 * summarized key falling in the L1 partition [12, 14):
 *
 * ```
 * L3  [0 ............... 8)
 * L2                      [8 ..... 12)
 * L1                                  [12 . 14)
 * ```
 *
 * Results in:
 *
 * ```
 * (level = L1 AND key >= 12 AND key < 14)
 * OR (level = L2 AND key >= 8 AND key < 12)
 * OR (level = L3 AND key >= 0 AND key < 8)
 * ```
 *
 * Each level contributes at most one contiguous range of partitions.
 */
final readonly class PartitionExpressionResolver
{
    /**
     * @param string $levelProperty The resolved property for the partition
     * level in the summary table
     * @param string $keyProperty The resolved property for the partition key in
     * the summary table
     * @param class-string<Partition> $partitionClass The class name of the
     * partition
     */
    public function __construct(
        private string $levelProperty,
        private string $keyProperty,
        private string $partitionClass,
    ) {}

    /**
     * @param mixed $lastKey The last partition key at the lowest level in the
     * summary table. The lowest level partition containing this key is
     * included in the result.
     */
    public function resolvePartitionExpression(mixed $lastKey): Criteria
    {
        $lowestLevel = PartitionUtil::getLowestLevel($this->partitionClass);
        $lastPartition = ($this->partitionClass)::createFromSourceValue($lastKey, $lowestLevel);

        $expressions = iterator_to_array($this->computeExpression($lastPartition), false);

        if (\count($expressions) === 1) {
            $expression = $expressions[0];
        } else {
            $expression = Criteria::expr()->orX(...$expressions);
        }

        return new Criteria(expression: $expression);
    }

    /**
     * Yields the expressions that cover everything from the beginning up to,
     * and including, the given partition. The expressions are yielded from
     * the lowest level to the highest level.
     *
     * @return iterable<Expression>
     */
    private function computeExpression(Partition $partition): iterable
    {
        $containing = $partition->getContaining();

        if ($containing === null) {
            // We are at the top level. Nothing can replace it, so we select
            // all the partitions on this level up to and including this
            // partition.

            yield Criteria::expr()->andX(
                Criteria::expr()->eq($this->levelProperty, $partition->getLevel()),
                Criteria::expr()->lt($this->keyProperty, $partition->getUpperBound()),
            );
        } elseif ($partition->getUpperBound() === $containing->getUpperBound()) {
            // This partition is the last child of its containing partition.
            // This partition and its preceding siblings together make up the
            // containing partition, so we use the containing partition
            // instead.

            yield from $this->computeExpression($containing);
        } else {
            // This partition is not the last child of its containing partition,
            // so the containing partition cannot be used as it also covers
            // values after this partition. We select this partition and its
            // preceding siblings, which are the partitions on this level from
            // the start of the containing partition up to the end of this
            // partition.

            yield Criteria::expr()->andX(
                Criteria::expr()->eq($this->levelProperty, $partition->getLevel()),
                Criteria::expr()->gte($this->keyProperty, $containing->getLowerBound()),
                Criteria::expr()->lt($this->keyProperty, $partition->getUpperBound()),
            );

            // Then we continue with everything before the containing
            // partition, starting from the partition just before it on the
            // containing level. If there is none, the containing partition is
            // at the beginning, and we are done.

            $previousOfContaining = $containing->getPrevious();

            if ($previousOfContaining !== null) {
                yield from $this->computeExpression($previousOfContaining);
            }
        }
    }
}
