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

use Rekalogika\Contracts\Rekapager\PageableInterface;

/**
 * Represent a cell, which is an object that contains dimension coordinates and
 * measures.
 *
 * The coordinates identify the cell: one member for each dimension in the
 * cell. The measures are the aggregated values over all the source entities
 * that match the coordinates. This interface is the common base of
 * `CubeCell`, the cell inside a query result, and the standalone cell
 * produced by `CoordinatesMapper`.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Cell
{
    /**
     * Get the coordinates of this cell, which is a collection of dimensions of
     * this cell.
     */
    public function getCoordinates(): Coordinates;

    /**
     * The measures of this cell, keyed by measure name.
     */
    public function getMeasures(): Measures;

    /**
     * The measure of this cell, if there is only one measure. If there are
     * multiple measures, this will return a null measure that contains no
     * value.
     */
    public function getMeasure(): Measure;

    /**
     * Get the source entities of this cell, if available. If the source
     * entities are not available, this will return null. The source entities
     * are the original entities that were aggregated to produce the measures in
     * this cell.
     *
     * @return null|PageableInterface<int,object>
     */
    public function getSourceEntities(): ?PageableInterface;
}
