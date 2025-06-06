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

use Rekalogika\Analytics\Exception\EmptyResultException;

/**
 * A query result in normalized tabular format. Each row in a normal table
 * contains one measure.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 *
 * @extends \Traversable<int,Row>
 */
interface NormalTable extends \Traversable, \Countable
{
    /**
     * @return class-string
     */
    public function getSummaryClass(): string;

    /**
     * @throws EmptyResultException
     */
    public function getRowPrototype(): NormalRow;
}
