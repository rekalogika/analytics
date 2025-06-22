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

use Rekalogika\Analytics\Common\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\GroupByExpressions;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final readonly class RollUpStrategy implements GroupingStrategy
{
    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): RollUp {

        $rollUp = new RollUp();

        foreach ($fields as $field) {
            if (
                !$field instanceof FieldSet
                && !$field instanceof Field
            ) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" does not support children of type "%s". Only "%s" or "%s" is allowed.',
                    self::class,
                    $field::class,
                    FieldSet::class,
                    Field::class,
                ));
            }

            $rollUp->add($field);
        }

        return $rollUp;
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
    ): string {
        return $fieldName;
    }
}
