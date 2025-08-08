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

namespace Rekalogika\PivotTable\Contracts;

interface Table
{
    /**
     * Returns the rows of the table.
     *
     * @return iterable<Row>
     */
    public function getRows(): iterable;

    /**
     * Gets the legend of a dimension or a measure.
     */
    public function getLegend(string $key): mixed;
}
