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
 * Supplies the possible members of a dimension, independently of any query
 * result. Used by the user interface to offer the available choices in a
 * filter, for example.
 *
 * Implement this interface to provide the members of dimensions that the
 * default implementation cannot handle. The default implementation handles
 * dimensions that are relations to other entities and dimensions that are
 * backed enums. Under Symfony, implementations are registered automatically
 * and tried in turn until one returns a non-null result, in the order of the
 * `priority` of their `rekalogika.analytics.distinct_values_resolver` tag,
 * highest first. The default implementation has priority -1000.
 */
interface DistinctValuesResolver
{
    /**
     * Returns the distinct values for the given dimension of the given class.
     * Returns null if the instance does not know how to handle the given
     * dimension, so that the next resolver in the chain can be tried.
     *
     * @param class-string $class The summary entity class name.
     * @param string $dimension The name of the dimension property.
     * @param int $limit The maximum number of values to return.
     * @return null|iterable<string,mixed> The distinct values. Key is the
     * identifier of each of the values, as understood by the `ValueSerializer`
     * of the dimension.
     */
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): null|iterable;
}
