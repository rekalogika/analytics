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

namespace Rekalogika\Analytics\Query;

/**
 * Represents a query result.
 *
 * For consumption only, do not implement. Methods may be added in the future.
 */
interface Result
{
    public function getTree(): TreeResult;

    public function getNormalTable(): NormalTable;

    public function getTable(): Table;
}
