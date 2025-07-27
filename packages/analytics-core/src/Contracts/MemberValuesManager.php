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

interface MemberValuesManager
{
    /**
     * Returns the distinct values for the given dimension of the given class.
     * Returns null if the instance does not know how to handle the given
     * dimension.
     *
     * @param class-string $class The summary entity class name.
     * @param string $dimension The name of the dimension property
     * @return null|iterable<string,mixed> The distinct values. Key is the
     * identifier of each of the values.
     */
    public function getDistinctValues(
        string $class,
        string $dimension,
        int $limit,
    ): null|iterable;
}
