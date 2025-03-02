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

namespace Rekalogika\Analytics;

interface DistinctValuesResolver
{
    /**
     * Iterable of arrays containing all the applicable class name and dimension
     * name. Or null if they are not known in advance. Used to optimize the
     * distinct values retrieval.
     *
     * @return null|iterable<array{class-string,string}>
     */
    public static function getApplicableDimensions(): null|iterable;

    /**
     * Returns the distinct values for the given dimension of the given class.
     * Returns null if the instance does not know how to handle the given
     * dimension.
     *
     * @param class-string $class The summary entity class name.
     * @param string $dimension The name of the dimension property
     * @return iterable<string,mixed>
     */
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): null|iterable;

    /**
     * @param class-string $class The summary entity class name.
     * @param string $dimension
     * @param string $id
     */
    public function getValueFromId(
        string $class,
        string $dimension,
        string $id,
    ): mixed;
}
