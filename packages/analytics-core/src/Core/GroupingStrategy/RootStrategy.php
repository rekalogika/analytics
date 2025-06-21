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
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final readonly class RootStrategy implements GroupingStrategy
{
    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): FieldSet|Cube|RollUp|GroupingSet {

        $groupingSet = new GroupingSet();

        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $field = new Cube($field);
            }

            $groupingSet->add($field);
        }

        return $groupingSet;
    }

    #[\Override]
    public function getGroupingFields(
        iterable $fields,
    ): iterable {
        foreach ($fields as $field) {
            yield $field => $field;
        }
    }

    #[\Override]
    public function getAssociatedGroupingField(
        string $fieldName,
    ): ?string {
        return $fieldName;
    }
}
