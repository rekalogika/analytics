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
 * Represent a cell in a cube. A cell has a tuple and one or more measures
 * associated with it.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface CubeCell
{
    /**
     * The input summary class for this cube.
     *
     * @return class-string
     */
    public function getSummaryClass(): string;

    public function getApex(): self;

    public function isNull(): bool;

    public function getTuple(): Tuple;

    public function getMeasures(): Measures;

    public function getMeasure(): Measure;

    public function rollUp(string $dimensionName): self;

    public function drillDown(string $dimensionName): CubeCells;

    // public function traverse(mixed ...$members): ?CubeCell;

    // /**
    //  * Determine if this cell was created to balance the tree, and does not
    //  * result from the query. If true, this node always leads to a dead end, and
    //  * won't have any measure on its leaves
    //  */
    // public function isNull(): bool;
}
