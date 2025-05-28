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

namespace Rekalogika\Analytics\Contracts\Summary;

use Doctrine\ORM\QueryBuilder;

/**
 * Implemented by summary entities that needs to modify the query builder used
 * for rolling-up the source.
 */
interface HasQueryBuilderModifier
{
    public static function modifyQueryBuilder(QueryBuilder $queryBuilder): void;
}
