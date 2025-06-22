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
use Rekalogika\Analytics\Contracts\Model\GroupingsConfiguration;
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
     */
    public function initializeGroupings(
        GroupingsConfiguration $configuration,
        iterable $fields,
    ): void;

    public function getAssociatedGroupingField(
        string $fieldName,
    ): ?string;
}
