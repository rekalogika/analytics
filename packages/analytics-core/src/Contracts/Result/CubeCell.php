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

use Doctrine\Common\Collections\Expr\Expression;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Represent a cell in a cube. A cell has coordinates and one or more measures
 * associated with it.
 *
 * A query result is a cube: the set of all the cells for every combination of
 * members of the query's dimensions, at every level of detail.
 * `Query::getResult()` returns the apex cell, and the methods of this interface
 * navigate from one cell to the others:
 *
 * - `drillDown()` goes one level more detailed, adding a dimension.
 * - `rollUp()` goes one level less detailed, removing a dimension.
 * - `slice()` and `find()` go to the child cell for one specific member.
 * - `dice()` changes the filter applied to the whole cube.
 * - `pivot()` reorders the dimensions.
 *
 * The cells are created lazily, and the cube remembers the cells it has
 * already created, so navigating to the same coordinates twice returns the
 * same instance.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface CubeCell extends Cell
{
    /**
     * The input summary class for this cube.
     *
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * The label of the summary class, as given in its `Summary` attribute.
     */
    public function getSummaryLabel(): TranslatableInterface;

    /**
     * The apex CubeCell, or the CubeCell without any dimensions. Its measures
     * are the grand totals of the whole cube. This is the cell returned by
     * `Query::getResult()`.
     */
    public function getApex(): self;

    /**
     * Determine if this cell was created for interpolation, or to balance the
     * cube, and does not result from the query.
     *
     * A null cell is created when a navigation method targets coordinates for
     * which the query returned no row, and when the framework fills the gaps
     * in a sequence dimension, e.g. a month without any order. The measures of
     * a null cell all have a null value.
     */
    public function isNull(): bool;

    /**
     * Pivot, or reorder the cells to the given dimensions order. The specified
     * dimensions must exist in the current coordinates, but may skip some
     * dimensions.
     *
     * The returned cell has the same members as this cell, but its coordinates
     * list the dimensions in the given order. Dimensions omitted from the list
     * are removed from the coordinates, as with `rollUp()`.
     *
     * @param list<string> $dimensions
     */
    public function pivot(array $dimensions): self;

    /**
     * Removes the given dimension(s) from the coordinates, and returns the
     * cell at the resulting, less detailed coordinates. Its measures are the
     * aggregates over every member of the removed dimensions. The opposite of
     * `drillDown()`.
     *
     * @param string|non-empty-list<string> $dimension The dimension(s) to
     * remove. Must be present in the coordinates of this cell.
     */
    public function rollUp(string|array $dimension): self;

    /**
     * Returns the child cells of this cell along the given dimension(s): one
     * cell for each member of the dimension present in the result under this
     * cell. The coordinates of each child are the coordinates of this cell
     * plus the member of the child.
     *
     * If several dimensions are given, the drill-down is nested, and the
     * returned cells have all of the given dimensions added to their
     * coordinates.
     *
     * ```php
     * foreach ($cell->drillDown('country') as $child) {
     *     $country = $child->getCoordinates()->get('country')?->getMember();
     *     $count = $child->getMeasures()->get('count')?->getValue();
     * }
     * ```
     *
     * @param string|non-empty-list<string> $dimension The dimension(s) to
     * add. Must be among the dimensions of the query, and not already in the
     * coordinates of this cell.
     */
    public function drillDown(string|array $dimension): CubeCells;

    /**
     * Returns the child cell for one specific member of the given dimension,
     * i.e. the cell whose coordinates are the coordinates of this cell plus
     * the given member. Returns a null cell (see `isNull()`) if the query
     * returned no row for these coordinates.
     *
     * @param string $dimension The dimension to add. Must be among the
     * dimensions of the query, and not already in the coordinates of this
     * cell.
     * @param mixed $member The member, matched against the members of the
     * dimension in the result. An object member is matched by identity, so it
     * should be an object obtained from the result, or a value object that the
     * dimension resolves to the same instance, such as a time bin.
     */
    public function slice(string $dimension, mixed $member): ?self;

    /**
     * Like `slice()`, but with looser matching. Iterates the children of this
     * cell along the given dimension, and returns the first one whose member
     * matches the argument. Returns null if none matches.
     *
     * A member matches if it is identical to the argument, if it is a
     * `MeasureMember` whose measure property equals the argument, if it is a
     * `\Stringable` whose string form equals the argument, or, if the argument
     * is a callable, if the callable returns true when given the member.
     *
     * @param string $dimension The dimension to search along.
     * @param mixed $argument The member, its string form, the measure name
     * for the `@values` dimension, or a `callable(mixed $member): bool`.
     */
    public function find(string $dimension, mixed $argument): ?self;

    /**
     * Returns the cell at the same coordinates as this cell, but in a cube
     * computed with the given dice predicate instead of the one of the
     * original query. This runs a new query. Pass null to remove the
     * predicate.
     *
     * See `Query::dice()` for the form of the predicate.
     */
    public function dice(?Expression $predicate): self;
}
