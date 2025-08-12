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

namespace Rekalogika\PivotTable\Contracts\Table;

/**
 * Represents a row in a result set, containing dimensions and measures. The
 * dimensions and measures must have unique keys.
 */
interface Row
{
    /**
     * The dimensions of the row. Keys are dimension names, values are the
     * dimension member.
     *
     * @return iterable<string,mixed>
     */
    public function getDimensions(): iterable;

    /**
     * The measures of the row. Keys are measure names, values are the measure
     * values.
     *
     * @return iterable<string,mixed>
     */
    public function getMeasures(): iterable;
}
