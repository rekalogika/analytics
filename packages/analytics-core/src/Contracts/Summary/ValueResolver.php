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

use Rekalogika\Analytics\Contracts\Context\SourceQueryContext;

/**
 * Describes how the value of a dimension is derived from the source entity.
 * Used in the `source` argument of the `Dimension` attribute, and in the
 * `Partition` attribute of the summary class.
 *
 * The derivation is a DQL expression evaluated by the database while the
 * source entities are rolled up into the summary table, e.g. a property path
 * such as `customer.country`, or a function applied to it.
 *
 * @see SourceQueryContext
 */
interface ValueResolver
{
    /**
     * Returns a DQL expression to transform the source value to the value
     * expected in the summary table.
     *
     * @param SourceQueryContext $context Resolves property paths of the source
     * entity into DQL, and binds parameters.
     */
    public function getExpression(SourceQueryContext $context): string;

    /**
     * The properties of the source entity that are involved in the calculation.
     * Used by Analytics to determine if a change in the source entity requires
     * a recalculation of the summary.
     *
     * If the value resolver takes another value resolver as input, it should
     * return the involved properties of the input value resolver.
     *
     * @return list<string>
     */
    public function getInvolvedProperties(): array;
}
