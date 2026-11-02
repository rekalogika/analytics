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

namespace Rekalogika\Analytics\Engine\SummaryQuery\DimensionFactory;

use Doctrine\Common\Collections\Order;

interface OrderByResolver
{
    /**
     * Returns the order to sort the members of the dimension, or null to keep
     * the order of the database result.
     */
    public function getDimensionNameOrderBy(string $name): ?Order;
}
