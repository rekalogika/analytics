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
use Rekalogika\Analytics\Contracts\Model\GroupingsConfiguration;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;

final readonly class FieldSetStrategy implements GroupingStrategy
{
    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): FieldSet {
        $fieldSet = new FieldSet();

        foreach ($fields as $field) {
            if (!$field instanceof Field) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" does not support children of type "%s". Only "%s" is allowed.',
                    self::class,
                    $field::class,
                    Field::class,
                ));
            }

            $fieldSet->add($field);
        }

        return $fieldSet;
    }

    /**
     * This strategy uses only a single grouping field for all its children.
     */
    #[\Override]
    public function initializeGroupings(
        GroupingsConfiguration $configuration,
        iterable $fields,
    ): void {
        foreach ($fields as $field) {
            $configuration->addGroupingField(
                identifier: 'all',
                sourcePropertyname: $field,
            );

            return;
        }
    }

    #[\Override]
    public function getAssociatedGroupingField(
        string $fieldName,
    ): string {
        return 'all';
    }
}
