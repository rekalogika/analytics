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

interface PartitionValueResolver extends ValueResolver
{
    public function transform(mixed $value): mixed;

    /**
     * Transform a specific summary table value to source value.
     */
    public function reverseTransform(mixed $value): int|string;
}
