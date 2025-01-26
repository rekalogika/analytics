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

namespace Rekalogika\DoctrineAdvancedGroupBy\Walker;

use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\Exec\SingleSelectSqlFinalizer;
use Doctrine\ORM\Query\Exec\SqlFinalizer;
use Doctrine\ORM\Query\OutputWalker;
use Doctrine\ORM\Query\SqlWalker;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\Item;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class CustomGroupBySqlWalker extends SqlWalker implements OutputWalker
{
    public const GROUP_BY = self::class . '::GROUP_BY';

    #[\Override]
    public function getFinalizer($AST): SqlFinalizer
    {
        if (!$AST instanceof SelectStatement) {
            throw new \RuntimeException('CustomGroupBySqlWalker requires a SelectStatement');
        }

        return new SingleSelectSqlFinalizer($this->walkSelectStatement($AST));
    }

    #[\Override]
    public function walkSelectStatement(SelectStatement $AST): string
    {
        // dummy value so that walkGroupByClause can be called
        $AST->groupByClause = new GroupByClause(['dummy']);

        return parent::walkSelectStatement($AST);
    }

    #[\Override]
    public function walkGroupByClause($groupByClause): string
    {
        $groupBy = $this->getQuery()->getHint(self::GROUP_BY);

        if (!$groupBy instanceof GroupBy) {
            throw new \RuntimeException('RollupSqlWalker requires a GroupBy hint');
        }

        return $this->walkCustomGroupByClause($groupBy);
    }

    public function walkCustomGroupByClause(GroupBy $groupBy): string
    {
        return ' GROUP BY DISTINCT '
            . implode(
                ', ',
                array_map(
                    $this->walkCustomGroupByItem(...),
                    iterator_to_array($groupBy),
                ),
            );
    }

    private function walkCustomGroupByItem(Item $item): string
    {
        if ($item instanceof Field) {
            return $this->walkCustomGroupByField($item);
        } elseif ($item instanceof Cube) {
            return $this->walkCustomGroupByCube($item);
        } elseif ($item instanceof RollUp) {
            return $this->walkCustomGroupByRollUp($item);
        } elseif ($item instanceof FieldSet) {
            return $this->walkCustomGroupByFieldset($item);
        } elseif ($item instanceof GroupingSet) {
            return $this->walkCustomGroupByGroupingSet($item);
        }

        throw new \RuntimeException('Unknown group by item');
    }

    private function walkCustomGroupByFieldset(FieldSet $fieldSet): string
    {
        return '('
            . implode(
                ', ',
                array_map(
                    $this->walkCustomGroupByField(...),
                    iterator_to_array($fieldSet),
                ),
            )
            . ')';
    }

    private function walkCustomGroupByField(Field $field): string
    {
        return $this->walkGroupByItem($field->getName());
    }

    private function walkCustomGroupByCube(Cube $cube): string
    {
        return
            ' CUBE('
            . implode(
                ', ',
                array_map(
                    $this->walkCustomGroupByItem(...),
                    iterator_to_array($cube),
                ),
            )
            . ')';
    }

    private function walkCustomGroupByRollUp(RollUp $rollUp): string
    {
        return
            ' ROLLUP('
            . implode(
                ', ',
                array_map(
                    $this->walkCustomGroupByItem(...),
                    iterator_to_array($rollUp),
                ),
            )
            . ')';
    }

    private function walkCustomGroupByGroupingSet(GroupingSet $groupingSet): string
    {
        return
            ' GROUPING SETS('
            . implode(
                ', ',
                array_map(
                    $this->walkCustomGroupByItem(...),
                    iterator_to_array($groupingSet),
                ),
            )
            . ')';
    }
}
