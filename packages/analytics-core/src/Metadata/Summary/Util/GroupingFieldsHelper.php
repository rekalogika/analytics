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

namespace Rekalogika\Analytics\Metadata\Summary\Util;

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;

final readonly class GroupingFieldsHelper
{
    private function __construct() {}

    /**
     * @param array<string,DimensionMetadata> $children
     * @return array<string,string>
     */
    public static function getGroupingFields(
        array $children,
        GroupingStrategy $groupingStrategy,
    ): array {
        $fields = array_keys($children);
        $groupingFields = $groupingStrategy->getGroupingFields($fields);

        $newGroupingFields = [];

        foreach ($groupingFields as $key => $field) {
            $child = $children[$field]
                ?? throw new LogicException(\sprintf(
                    'Dimension does not have child with property name "%s".',
                    $field,
                ));

            $childGroupingFields = $child->getGroupingFields();

            if ($childGroupingFields === null) {
                $fqKey = $key;
                $fqField = $field;
                $newGroupingFields[$fqKey] = $fqField;
            } else {
                foreach ($childGroupingFields as $childKey => $childField) {
                    $fqKey = $key . '.' . $childKey;
                    $fqField = $field . '.' . $childField;
                    $newGroupingFields[$fqKey] = $fqField;
                }
            }
        }

        return $newGroupingFields;
    }
}
