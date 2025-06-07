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

interface AggregateFunction
{
    /**
     * Get the DQL function to aggregate data from the source table to the summary
     * table.
     */
    public function getSourceToSummaryDQLFunction(Context $context): ?string;

    /**
     * Get the DQL function to roll up data from the summary to a higher level
     * in partitioning. The template '%s' will be replaced with the field name.
     */
    public function getSummaryToSummaryDQLFunction(): ?string;

    /**
     * Reads the field from the summary table and convert for human consumption.
     * The template '%s' will be replaced with the field name.
     */
    public function getSummaryReaderDQLFunction(SummaryContext $context): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
