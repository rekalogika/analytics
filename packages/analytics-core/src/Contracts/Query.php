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
use Rekalogika\Analytics\Contracts\Result\Result;

/**
 * A query object for a summary table
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Query
{
    //
    // from
    //

    /**
     * @return class-string
     */
    public function getFrom(): string;

    /**
     * @param class-string $class
     */
    public function from(string $class): static;

    //
    // group by
    //

    /**
     * @return list<string>
     */
    public function getGroupBy(): array;

    public function groupBy(string ...$dimensions): static;

    public function addGroupBy(string ...$dimensions): static;

    //
    // select
    //

    /**
     * @return list<string>
     */
    public function getSelect(): array;

    public function select(string ...$measures): static;

    public function addSelect(string ...$measures): static;

    //
    // where
    //

    /**
     * @return list<Expression>
     */
    public function getWhere(): array;

    public function where(Expression $expression): static;

    public function andWhere(Expression $expression): static;

    //
    // order by
    //

    /**
     * @return array<string,Order>
     */
    public function getOrderBy(): array;

    public function orderBy(
        string $field,
        Order $direction = Order::Ascending,
    ): static;

    public function addOrderBy(
        string $field,
        Order $direction = Order::Ascending,
    ): static;

    //
    // result
    //

    public function getResult(): Result;
}
