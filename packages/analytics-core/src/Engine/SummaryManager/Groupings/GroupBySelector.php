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

namespace Rekalogika\Analytics\Engine\SummaryManager\Groupings;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\DoctrineAdvancedGroupBy\Collector\NodeCollector;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\Item;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

final class GroupBySelector
{
    private NodeCollector $collector;

    /**
     * @var list<Field>
     */
    private array $selectedFields = [];

    public function __construct(
        GroupBy $groupBy,
    ) {
        $this->collector = new NodeCollector($groupBy);
    }

    public function select(string $fieldContent): void
    {
        $field = $this->collector->getFieldsByContent($fieldContent);

        if ($field === []) {
            throw new InvalidArgumentException(\sprintf('Field "%s" not found in group by.', $fieldContent));
        }

        if (\count($field) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'More than one field is not supported. Found "%s" fields with content "%s".',
                \count($field),
                $fieldContent,
            ));
        }

        $this->selectField($field[0]);
    }

    /**
     * @return list<Field>
     */
    public function getSelectedFields(): array
    {
        return $this->selectedFields;
    }

    private function getParent(Item $item): ?Item
    {
        return $this->collector->getParent($item);
    }

    private function register(Field $field): void
    {
        if (\in_array($field, $this->selectedFields, true)) {
            return;
        }

        $this->selectedFields[] = $field;
    }

    private function selectField(Field $field): void
    {
        $this->register($field);

        $parent = $this->getParent($field);

        if ($parent instanceof FieldSet) {
            $this->selectFieldSet($parent);
        } // ...
    }

    private function selectFieldSet(FieldSet $fieldSet): void
    {
        foreach ($fieldSet as $item) {
            $this->register($item);
        }

        $parent = $this->getParent($fieldSet);

        if ($parent instanceof GroupingSet) {
            $this->selectGroupingSet($parent, $fieldSet);
        } elseif ($parent instanceof GroupBy) {
            $this->selectGroupBy($parent);
        } elseif ($parent instanceof Cube) {
            $this->selectCube($parent);
        } elseif ($parent instanceof RollUp) {
            $this->selectRollUp($parent, $fieldSet);
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Unexpected parent type "%s" for FieldSet.',
                get_debug_type($parent),
            ));
        }
    }

    private function selectCube(Cube $cube): void
    {
        $parent = $this->getParent($cube);

        if ($parent instanceof GroupingSet) {
            $this->selectGroupingSet($parent, $cube);
        } elseif ($parent instanceof GroupBy) {
            $this->selectGroupBy($parent);
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Unexpected parent type "%s" for Cube.',
                get_debug_type($parent),
            ));
        }
    }

    private function selectRollUp(RollUp $rollUp, FieldSet|Field $incoming): void
    {
        foreach ($rollUp as $child) {
            if ($child === $incoming) {
                break;
            }

            if ($child instanceof Field) {
                $this->selectField($child);
            } else {
                $this->selectFieldSet($child);
            }
        }

        $parent = $this->getParent($rollUp);

        if ($parent instanceof GroupingSet) {
            $this->selectGroupingSet($parent, $rollUp);
        } elseif ($parent instanceof GroupBy) {
            $this->selectGroupBy($parent);
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Unexpected parent type "%s" for Cube.',
                get_debug_type($parent),
            ));
        }
    }

    private function selectGroupingSet(
        GroupingSet $groupingSet,
        FieldSet|Cube|RollUp|GroupingSet $incoming,
    ): void {
        $parent = $this->getParent($groupingSet);

        if ($parent instanceof GroupBy) {
            $this->selectGroupBy($parent);
        } elseif ($parent instanceof GroupingSet) {
            $this->selectGroupingSet($parent, $groupingSet);
        } else {
            throw new InvalidArgumentException(\sprintf(
                'Unexpected parent type "%s" for GroupingSet.',
                get_debug_type($parent),
            ));
        }
    }

    private function selectGroupBy(GroupBy $groupBy): void
    {
        // done
    }
}
