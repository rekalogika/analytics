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
    /**
     * @param list<string>|null $ordering
     */
    public function __construct(
        private ?array $ordering = null,
    ) {}

    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): RollUp {
        $ordering = $this->ordering ?? array_keys(iterator_to_array($fields));

        $rollUp = new RollUp();

        foreach ($ordering as $fieldName) {
            $field = $fields->get($fieldName);

            if ($field === null) {
                throw new InvalidArgumentException(\sprintf(
                    'Field "%s" is not defined in the group-by expressions. Available fields: %s',
                    $fieldName,
                    implode(', ', array_keys(iterator_to_array($fields))),
                ));
            }

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
}
