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

namespace Rekalogika\Analytics\Contracts\Summary;

/**
 * The value resolver of the partition of a summary class. It resolves the
 * partitioning value of the source entity, e.g. its primary key, and converts
 * it between the representation in the source and the integer the partition
 * works with.
 *
 * Used in the `Partition` attribute of the summary class.
 *
 * @template T The type of the partitioning value in the source entity, e.g.
 * `int` for an integer primary key.
 */
interface PartitionValueResolver extends ValueResolver
{
    /**
     * The property of the source entity that is used as the source value.
     * Unlike ValueResolver, here we return one and only one value.
     *
     * @return list{string}
     */
    #[\Override]
    public function getInvolvedProperties(): array;

    /**
     * Transforms a source value to the summary value in PHP. It must be able to
     * accept input in string format because the value may come from a CLI
     * argument, e.g. the start and end of `SummaryManager::refresh()`.
     *
     * @param T|string $value
     * @return int The integer the partition is computed from.
     */
    public function transformSourceValueToSummaryValue(mixed $value): int;

    /**
     * Transforms a summary value to the source value in PHP. The inverse of
     * `transformSourceValueToSummaryValue()`, used e.g. to query the source
     * entities belonging to a partition.
     *
     * @return T
     */
    public function transformSummaryValueToSourceValue(int $value): mixed;
}
