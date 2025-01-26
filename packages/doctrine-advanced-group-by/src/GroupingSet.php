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

/**
 * @implements \IteratorAggregate<FieldSet|RollUp|Cube>
 */
final class GroupingSet implements Item, \IteratorAggregate
{
    /**
     * @var list<FieldSet|RollUp|Cube>
     */
    private array $items = [];

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }

    #[\Override]
    public function getSignature(): string
    {
        return hash(
            'xxh128',
            self::class . implode(
                '',
                array_map(
                    fn(FieldSet|RollUp|Cube $item): string => $item->getSignature(),
                    $this->items,
                ),
            ),
        );
    }

    public static function create(FieldSet|RollUp|Cube ...$items): self
    {
        $groupingSet = new self();

        foreach ($items as $item) {
            $groupingSet->addItem($item);
        }

        return $groupingSet;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function addItem(FieldSet|RollUp|Cube $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function flatten(): GroupingSet
    {
        $fieldSets = [];

        foreach ($this->items as $item) {
            if ($item instanceof RollUp || $item instanceof Cube) {
                $item = $item->flatten();
            } else { // instanceof FieldSet
                $item = self::create($item);
            }

            foreach ($item as $fieldSet) {
                if (!$fieldSet instanceof FieldSet) {
                    throw new \RuntimeException('Expected FieldSet');
                }

                $fieldSets[$fieldSet->getSignature()] = $fieldSet;
            }
        }

        $groupingSet = new GroupingSet();

        foreach ($fieldSets as $fieldSet) {
            $groupingSet->addItem($fieldSet);
        }

        return $groupingSet;
    }
}
