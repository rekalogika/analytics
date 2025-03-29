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
use Doctrine\Common\Collections\Order;

/**
 * A query object for a summary table
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Query
{
    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * @return list<string>
     */
    public function getGroupBy(): array;

    /**
     * @return list<string>
     */
    public function getSelect(): array;

    /**
     * @return list<Expression>
     */
    public function getWhere(): array;

    /**
     * @return array<string,Order>
     */
    public function getOrderBy(): array;
}
