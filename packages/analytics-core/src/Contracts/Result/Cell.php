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

namespace Rekalogika\Analytics\Contracts\Result;

/**
 * Represent a cell, which is an object that contains a tuple and measures.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Cell
{
    /**
     * Get the tuple of this cell, which is a collection of dimensions of this
     * cell.
     */
    public function getTuple(): Tuple;

    /**
     * The measures of this cell.
     */
    public function getMeasures(): Measures;

    /**
     * The measure of this cell, if there is only one measure. If there are
     * multiple measures, this will return a null measure that contains no
     * value.
     */
    public function getMeasure(): Measure;
}
