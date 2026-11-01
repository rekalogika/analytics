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

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Exception\GroupingSetNotAvailable;
use Rekalogika\Analytics\Contracts\Exception\InterpolationOverflowException;
use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\QueryResultOverflowException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;

/**
 * A query object for a summary table.
 *
 * Obtain an instance from `SummaryManager::createQuery()`, configure it using
 * the fluent methods below, then call `getResult()` to execute it. The result
 * is the apex cell of the data cube, from which the rest of the cube can be
 * navigated by slicing, dicing, rolling up, drilling down, and pivoting.
 *
 * ```php
 * $cell = $summaryManager->createQuery()
 *     ->from(OrderSummary::class)
 *     ->withDimensions('time.civil.year', 'customerCountry')
 *     ->dice(Criteria::expr()->eq('time.civil.year', 2024))
 *     ->orderBy('customerCountry')
 *     ->getResult();
 * ```
 *
 * The query object is mutable. The setters return the same instance, and
 * changing any parameter discards the cached result, so the next call to
 * `getResult()` runs the query again.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Query
{
    //
    // from
    //

    /**
     * Returns the summary class this query reads from.
     *
     * @return class-string
     * @throws InvalidArgumentException If `from()` has not been called yet.
     */
    public function getFrom(): string;

    /**
     * Sets the summary class to query. This must be called before any of the
     * other setters, because they validate the field names against the
     * metadata of the summary class.
     *
     * @param class-string $class The summary entity class, i.e. a class
     * having the `Summary` attribute.
     */
    public function from(string $class): static;

    //
    // dimension
    //

    /**
     * Returns the dimensions of the query, in the order they were given. The
     * special `@values` dimension is always present. If the caller did not
     * specify it, it is appended to the end of the list.
     *
     * @return list<string>
     */
    public function getDimensions(): array;

    /**
     * Replaces the dimensions of the query. The result will be grouped by
     * these dimensions, and the order given here determines the order of the
     * dimensions in the coordinates of the resulting cells.
     *
     * Each name must be a dimension of the summary class. Dimensions inside a
     * dimension group are referred to using dot notation, e.g.
     * `time.civil.year`. The special `@values` dimension represents the
     * measures. It may be placed anywhere in the list, which is useful for
     * controlling where the measures appear in a pivot table.
     *
     * Duplicates are removed.
     *
     * @throws InvalidArgumentException If one of the names is not a dimension
     * of the summary class.
     */
    public function withDimensions(string ...$dimensions): static;

    /**
     * Appends dimensions to the ones already in the query. See
     * `withDimensions()` for the accepted names.
     */
    public function addDimension(string ...$dimensions): static;

    //
    // dice
    //

    /**
     * Returns the dice predicate, or null if the query has none. If several
     * predicates were added using `andDice()`, they are combined with AND.
     */
    public function getDice(): ?Expression;

    /**
     * Replaces the dice predicate, i.e. the filter applied to the summary
     * rows before aggregation. Pass null to remove the filter.
     *
     * The predicate is a Doctrine Collections expression, usually built using
     * `Criteria::expr()`. The field names in the expression are dimensions of
     * the summary class, in the same notation as `withDimensions()`. The
     * values are compared against the dimension members, so an object member
     * (e.g. a related entity or a time bin) can be used directly as the value.
     */
    public function dice(?Expression $predicate): static;

    /**
     * Adds a predicate to the dice. The new predicate is combined with the
     * existing ones using AND.
     */
    public function andDice(Expression $predicate): static;

    //
    // order by
    //

    /**
     * Returns the ordering of the query. The key is the field name, the value
     * is the direction. The entries are in order of priority.
     *
     * @return array<string,Order>
     */
    public function getOrderBy(): array;

    /**
     * Replaces the ordering with a single field.
     *
     * @param string $field A dimension or a measure of the summary class.
     * Ordering by the special `@values` dimension is not supported.
     */
    public function orderBy(
        string $field,
        Order $direction = Order::Ascending,
    ): static;

    /**
     * Adds a field to the ordering, with a lower priority than the fields
     * already present. See `orderBy()` for the accepted fields.
     */
    public function addOrderBy(
        string $field,
        Order $direction = Order::Ascending,
    ): static;

    //
    // result
    //

    /**
     * Executes the query and returns the apex cell of the resulting cube. The
     * apex cell has no dimensions in its coordinates, and its measures are the
     * grand totals over every row matched by the dice predicate. Use
     * `drillDown()`, `slice()` and friends on the returned cell to reach the
     * other cells.
     *
     * The result is cached until one of the parameters of the query is
     * changed.
     *
     * @throws GroupingSetNotAvailable If the summary table does not store the
     * combination of dimensions needed by the query.
     * @throws QueryResultOverflowException If the query returns more rows than
     * the configured safeguard limit.
     * @throws InterpolationOverflowException If filling the gaps in the result
     * would create too many cells.
     */
    public function getResult(): CubeCell;
}
