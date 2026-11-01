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

namespace Rekalogika\Analytics\Contracts\Model;

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;

/**
 * Describes how the rows of a summary table are partitioned.
 *
 * Each source entity has a partitioning value, e.g. its integer primary key,
 * resolved by the `PartitionValueResolver` of the summary. The value is
 * classified into a partition, which is a contiguous range of partitioning
 * values. The summary table stores one set of aggregated rows per partition,
 * which allows the framework to refresh only the partitions touched by a
 * change in the source, instead of the whole table.
 *
 * Partitions are organized in levels. A partition at a higher level covers a
 * larger range, and contains whole partitions of the next lower level. The
 * framework stores aggregates at every level, so a query spanning a long
 * range can read a few high-level rows instead of many low-level ones.
 *
 * Implementations are Doctrine embeddables that are embedded in the summary
 * entity, holding the level and the key as columns. See `IntegerPartition` for
 * the implementation used for integer primary keys. The string form of a
 * partition is for display and debugging.
 */
interface Partition extends \Stringable
{
    /**
     * Returns all the levels of partitioning that can be used, from the
     * highest (covering the largest range) to the lowest (covering the
     * smallest range).
     *
     * @return non-empty-list<int>
     */
    public static function getAllLevels(): array;

    /**
     * Returns the level of this partition. One of the values returned by
     * `getAllLevels()`.
     */
    public function getLevel(): int;

    /**
     * Returns the key of this partition. The key identifies the partition
     * among the partitions of the same level. Together with the level, it
     * identifies the partition uniquely.
     */
    public function getKey(): int;

    /**
     * Creates a partition from the source value and level, i.e. the partition
     * at the given level that contains the given source value.
     *
     * @param mixed $source The partitioning value of a source entity, as
     * resolved by the `PartitionValueResolver`.
     * @param int $level One of the values returned by `getAllLevels()`.
     */
    public static function createFromSourceValue(
        mixed $source,
        int $level,
    ): self;

    /**
     * The lowest value of the partition, inclusive. This must be the same as
     * the upper bound of the previous neighboring partition.
     */
    public function getLowerBound(): int;

    /**
     * The highest value of the partition, exclusive. This must be the same as
     * the lower bound of the next neighboring partition.
     */
    public function getUpperBound(): int;

    /**
     * Returns the partition at the next higher level that contains this
     * partition, or null if this partition is already at the highest level.
     */
    public function getContaining(): ?static;

    /**
     * Returns the next neighboring partition on the same level as this
     * partition, or null if there is none.
     */
    public function getNext(): ?static;

    /**
     * Returns the previous neighboring partition on the same level as this
     * partition, or null if there is none.
     */
    public function getPrevious(): ?static;

    /**
     * Returns a DQL expression that takes a partitioning key value from the
     * source, and outputs the partition key used in the summary table.
     * Essentially, it classifies the source into a partition in the summary
     * table.
     *
     * The framework uses the expression in the GROUP BY clause of the query
     * that rolls up the source entities, so that the rows of each partition
     * are aggregated separately.
     *
     * The framework also uses it when rolling up a lower level of the summary
     * table to a higher level. In this case, the input is the key of the lower
     * level partition, and the expression must give the key of the higher
     * level partition containing it.
     *
     * @template T
     * @param PartitionValueResolver<T> $input The resolver that gives the
     * DQL expression of the integer value to classify: the partitioning value
     * of the source entity, or the key of a lower level partition.
     * @param int $level The level of the partitions to classify into.
     * @param SourceQueryContext $context The context of the roll-up query.
     */
    public static function getClassifierExpression(
        PartitionValueResolver $input,
        int $level,
        SourceQueryContext $context,
    ): string;
}
