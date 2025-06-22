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
 * @internal Will change in the future.
 */
interface GroupingStrategy
{
    /**
     * Returns the group-by expression for this dimension. The parent dimension
     * gets the result of this method as the input of the same method.
     *
     * @param GroupByExpressions $fields The group by expressions from the
     * properties of the class.
     */
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): FieldSet|Cube|RollUp|GroupingSet;

    /**
     * Grouping fields used by this dimension.
     *
     * @param iterable<string> $fields All the field names of the children.
     * Fields are not fully qualified.
     * @return iterable<string,string> Key is the identifier of the grouping
     * field, will be used to identify the grouping field in the framework.
     * Value is the field name of the children. Both are not fully qualified.
     */
    public function getGroupingFields(
        iterable $fields,
    ): iterable;

    public function getAssociatedGroupingField(
        string $fieldName,
    ): ?string;
}
