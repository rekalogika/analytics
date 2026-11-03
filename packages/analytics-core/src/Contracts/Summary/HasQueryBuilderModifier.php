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
 *
 * This is the way to restrict which source entities are summarized, e.g. to
 * exclude cancelled orders or soft-deleted records. The framework applies the
 * modification to the queries it makes against the source entities: the
 * roll-up into the summary table, the determination of the key range to
 * refresh, and the listing of the source entities of a cell. It is not
 * applied when looking for the source entities affected by a change to a
 * related entity, the change can move them out of the filter.
 */
interface HasQueryBuilderModifier
{
    /**
     * Modifies the query builder, typically by adding WHERE conditions. The
     * root alias of the source entity can be obtained from the query builder.
     */
    public static function modifyQueryBuilder(QueryBuilder $queryBuilder): void;
}
