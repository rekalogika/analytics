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

namespace Rekalogika\Analytics\Contracts\Model;

use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * List of available fields for the group by clause in a query.
 *
 * Given to `GroupingStrategy::getGroupByExpression()`. The keys are the names
 * of the dimensions directly under the summary class or the dimension group
 * the strategy is attached to. The value for a plain dimension is a `Field`.
 * The value for a dimension group is the expression produced by the group's
 * own grouping strategy, so nested groups compose bottom-up.
 *
 * @see \Rekalogika\Analytics\Contracts\Summary\GroupingStrategy
 *
 * @extends \Traversable<string,Field|FieldSet|Cube|RollUp|GroupingSet>
 */
interface GroupByExpressions extends \Traversable
{
    /**
     * Returns the group by expression of the given dimension, or null if there
     * is no such dimension.
     */
    public function get(
        string $name,
    ): Field|FieldSet|Cube|RollUp|GroupingSet|null;
}
