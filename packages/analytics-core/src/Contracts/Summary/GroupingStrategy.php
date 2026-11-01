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

use Rekalogika\Analytics\Contracts\Model\GroupByExpressions;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * Determines which combinations of dimensions, or grouping sets, are
 * pre-aggregated in the summary table. Attached to a summary class using the
 * `Summary` attribute, and to a dimension group using the `DimensionGroup`
 * attribute.
 *
 * The strategy produces one of the advanced GROUP BY constructs of
 * `rekalogika/doctrine-advanced-group-by`, such as `Cube`, `RollUp`, or
 * `GroupingSet`. Storing every combination (a cube) supports any query, at
 * the cost of table size. Storing fewer combinations keeps the table small,
 * but a query needing a combination that is not stored fails with
 * `GroupingSetNotAvailable`.
 *
 * @see GroupByExpressions
 */
interface GroupingStrategy
{
    /**
     * Returns the group-by expression for this dimension. The parent dimension
     * gets the result of this method as the input of the same method.
     *
     * @param GroupByExpressions $fields The group by expressions from the
     * properties of the class, keyed by dimension name. For a dimension group,
     * the expressions of its child dimensions. For the summary class, the
     * expressions of its top-level dimensions.
     * @return FieldSet|Cube|RollUp|GroupingSet The combined group by
     * expression.
     */
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): FieldSet|Cube|RollUp|GroupingSet;
}
