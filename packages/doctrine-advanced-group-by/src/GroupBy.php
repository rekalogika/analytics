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

namespace Rekalogika\DoctrineAdvancedGroupBy;

use Doctrine\ORM\Query;
use Rekalogika\DoctrineAdvancedGroupBy\Walker\CustomGroupBySqlWalker;

/**
 * @implements \IteratorAggregate<Item>
 */
final class GroupBy implements \IteratorAggregate, Item
{
    public function apply(Query $query): void
    {
        $query
            ->setHint(
                name: Query::HINT_CUSTOM_OUTPUT_WALKER,
                value: CustomGroupBySqlWalker::class,
            )
            ->setHint(
                name: CustomGroupBySqlWalker::GROUP_BY,
                value: $this,
            );
    }

    /**
     * @var list<Cube|Field|FieldSet|GroupingSet|RollUp>
     */
    private array $items = [];

    #[\Override]
    public function getSignature(): string
    {
        return hash(
            'xxh128',
            self::class . implode(
                '',
                array_map(
                    fn(Item $item): string => $item->getSignature(),
                    $this->items,
                ),
            ),
        );
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function addItem(Cube|Field|FieldSet|GroupingSet|RollUp $item): self
    {
        if (\count($item) === 0) {
            return $this;
        }

        $this->items[] = $item;

        return $this;
    }

    public function flatten(): GroupBy
    {
        $items = [];

        foreach ($this->items as $item) {
            if ($item instanceof Cube || $item instanceof RollUp) {
                $item = $item->flatten();
                $items[] = $item;
            } elseif ($item instanceof FieldSet) {
                foreach ($item as $field) {
                    $items[] = $field;
                }
            } else {
                $items[] = $item;
            }
        }

        $results = $this->generateCombinations($items);

        $groupingSet = new GroupingSet();

        foreach ($results as $result) {
            $fieldSet = new FieldSet();

            foreach ($result as $item) {
                $fieldSet->addField($item);
            }

            $groupingSet->addItem($fieldSet);
        }

        $groupBy = new GroupBy();
        $groupBy->addItem($groupingSet);

        return $groupBy;
    }

    /**
     * @param list<Field|GroupingSet> $items
     * @return list<list<Field>>
     */
    private function generateCombinations(array $items): array
    {
        $first = array_shift($items);

        if ($first === null) {
            return [[]];
        } elseif ($first instanceof Field) {
            $results = [];
            $combinations = $this->generateCombinations($items);

            foreach ($combinations as $combination) {
                $results[] = array_merge([$first], $combination);
            }

            return $results;
        } else { // instanceof GroupingSet
            $results = [];
            $first = $first->flatten();

            foreach ($first as $fieldSet) {
                if (!$fieldSet instanceof FieldSet) {
                    throw new \RuntimeException('Unsupported property');
                }

                foreach ($this->generateCombinations($items) as $combination) {
                    $results[] = array_values(array_merge(iterator_to_array($fieldSet), $combination));
                }
            }

            return $results;
        }
    }
}
