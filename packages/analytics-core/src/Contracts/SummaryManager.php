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

namespace Rekalogika\Analytics\Contracts;

/**
 * The entry point of the framework. Refreshes summary tables and creates
 * queries against them. Under Symfony, inject it by type-hinting this
 * interface.
 */
interface SummaryManager
{
    /**
     * Refreshes a range of a summary table from its source entities. This is
     * the method behind the `rekalogika:analytics:refresh:range` command, and
     * is meant for the initial population of a summary table, or for
     * recomputing part of it. Changes made to the source entities afterwards
     * are picked up by the refresh agent without calling this method.
     *
     * The range is expressed in partitioning keys of the source entities, e.g.
     * their integer primary keys, as resolved by the `PartitionValueResolver`
     * of the summary's partition.
     *
     * The method blocks while the refresh agent is refreshing the same
     * summary, so that the two do not process the same partition at the same
     * time.
     *
     * @param class-string $class The summary entity class.
     * @param int|string|null $start The first key to process, inclusive. If
     * null, the refresh continues from the last key previously processed, or
     * from the earliest key in the source if the summary is empty.
     * @param int|string|null $end The last key to process, inclusive. If null,
     * the refresh runs until the latest key present in the source.
     * @param int $batchSize The number of partitions to process in one unit
     * of work. It applies to every partition level: the lowest level is
     * refreshed in batches of this many partitions, then each upper level is
     * rolled up in batches of this many partitions of that level. Must be at
     * least 1, otherwise an `InvalidArgumentException` is thrown.
     * @param string|null $resumeId The identifier of the batch to resume from,
     * as reported when a previous run was interrupted. Batches before it are
     * skipped.
     */
    public function refresh(
        string $class,
        int|string|null $start,
        int|string|null $end,
        int $batchSize = 1,
        ?string $resumeId = null,
    ): void;

    /**
     * Marks the lowest partitions containing the source entities with the
     * given partitioning keys as dirty, and schedules the refresh agent to
     * refresh them. This is for changes the framework cannot detect, e.g. a
     * change made using DQL `UPDATE`, native SQL, or a database cascade.
     * Changes made through the ORM are detected automatically.
     *
     * The flags are inserted using the connection of the summary's entity
     * manager, without flushing it, and are part of its current transaction,
     * if any. The refresh agent processes them after the delay determined by
     * the refresh agent strategy.
     *
     * @param class-string $class The summary entity class.
     * @param iterable<int|string> $keys The partitioning keys of the source
     * entities, e.g. their primary keys, as resolved by the
     * `PartitionValueResolver` of the summary's partition.
     */
    public function markDirty(string $class, iterable $keys): void;

    /**
     * Marks every lowest partition from the one containing `$start` to the
     * one containing `$end`, inclusive, as dirty, and schedules the refresh
     * agent to refresh them. One flag is created for each partition, so for
     * a large range, consider `refresh()` instead. See `markDirty()`.
     *
     * @param class-string $class The summary entity class.
     * @param int|string $start The first partitioning key, inclusive.
     * @param int|string $end The last partitioning key, inclusive. Must not
     * be lower than `$start`, otherwise an `InvalidArgumentException` is
     * thrown.
     */
    public function markDirtyRange(
        string $class,
        int|string $start,
        int|string $end,
    ): void;

    /**
     * Creates a new, empty query. Call `from()` on the returned query to set
     * the summary class before setting the other parameters.
     */
    public function createQuery(): Query;
}
