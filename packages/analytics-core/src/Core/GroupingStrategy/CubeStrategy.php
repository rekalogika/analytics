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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Model\GroupByExpressions;
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;

final readonly class CubeStrategy implements GroupingStrategy
{
    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): Cube {

        $cube = new Cube();

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

            $cube->add($field);
        }

        return $cube;
    }
}
