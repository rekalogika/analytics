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
 * Calculates the expression describing all the partitions used for queries.
 */
final readonly class PartitionExpressionResolver
{
    /**
     * @param class-string<Partition> $partitionClass The class name of the partition
     */
    public function __construct(
        private string $levelProperty,
        private string $keyProperty,
        private string $partitionClass,
    ) {}

    /**
     * @param mixed $lastKey The last partitioning key in the summary table
     */
    public function resolvePartitionExpression(mixed $lastKey): Criteria
    {
        $lowestLevel = PartitionUtil::getLowestLevel($this->partitionClass);
        $pointPartition = ($this->partitionClass)::createFromSourceValue($lastKey, $lowestLevel);

        $expressions = iterator_to_array($this->computeExpression($pointPartition));

        if (\count($expressions) > 1) {
            $expressions = Criteria::expr()->orX(...$expressions);
        } else {
            $expressions = $expressions[0];
        }

        return new Criteria(expression: $expressions);
    }

    /**
     * @return iterable<Expression>
     */
    private function computeExpression(Partition $partition): iterable
    {
        $higherPartition = $partition->getContaining();

        if ($higherPartition === null) {
            // if the partition is at the top level, return all top partitions
            // up to the partition
            yield Criteria::expr()->andX(
                Criteria::expr()->eq($this->levelProperty, $partition->getLevel()),
                Criteria::expr()->lt($this->keyProperty, $partition->getUpperBound()),
            );
        } elseif ($partition->getUpperBound() === $higherPartition->getUpperBound()) {
            // if the partition is at the end of the containing parent partition,
            // return the containing parent partition
            foreach ($this->computeExpression($higherPartition) as $expr) {
                yield $expr;
            }
        } else {
            // else return the range of the current level from the start of the
            // containing parent partition up to the end of the current
            // partition

            yield Criteria::expr()->andX(
                Criteria::expr()->eq($this->levelProperty, $partition->getLevel()),
                Criteria::expr()->gte($this->keyProperty, $higherPartition->getLowerBound()),
                Criteria::expr()->lt($this->keyProperty, $partition->getUpperBound()),
            );

            // and then return the range of the previous of the parent partition

            $higherPrevious = $higherPartition->getPrevious();

            if ($higherPrevious !== null) {
                foreach ($this->computeExpression($higherPrevious) as $expr) {
                    yield $expr;
                }
            }
        }
    }
}
