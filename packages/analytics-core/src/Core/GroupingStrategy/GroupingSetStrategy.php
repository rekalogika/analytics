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

namespace Rekalogika\Analytics\Core\GroupingStrategy;

use Rekalogika\Analytics\Contracts\Model\GroupByExpressions;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;

final readonly class GroupingSetStrategy implements GroupingStrategy
{
    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): GroupingSet {

        $groupingSet = new GroupingSet();

        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $field = new FieldSet($field);
            }

            $groupingSet->add($field);
        }

        return $groupingSet;
    }
}
