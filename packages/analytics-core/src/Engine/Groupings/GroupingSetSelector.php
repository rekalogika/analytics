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

namespace Rekalogika\Analytics\Engine\Groupings;

use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\DoctrineAdvancedGroupBy\Cube;
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;
use Rekalogika\DoctrineAdvancedGroupBy\RollUp;

/**
 * Finds the smallest stored grouping set that contains all the requested
 * fields.
 *
 * A GROUP BY clause is the cross product of its items, so every stored
 * grouping set is the union of one alternative from each item. Because the
 * items do not share fields, the smallest covering set is the union of the
 * smallest covering alternative of each item. This avoids flattening the
 * whole GROUP BY clause, which can be thousands of sets for a cube.
 *
 * @internal
 */
final readonly class GroupingSetSelector
{
    /**
     * For each item in the GROUP BY clause, the list of its alternatives,
     * each alternative is a list of field contents.
     *
     * @var list<list<list<string>>>
     */
    private array $items;

    public function __construct(GroupBy $groupBy)
    {
        $items = [];

        foreach ($groupBy as $item) {
            if ($item instanceof Field) {
                $items[] = [[$item->getContent()]];
            } elseif ($item instanceof FieldSet) {
                $items[] = [self::getContents($item)];
            } elseif (
                $item instanceof Cube
                || $item instanceof RollUp
                || $item instanceof GroupingSet
            ) {
                $alternatives = [];

                foreach ($item->flatten() as $fieldSet) {
                    if (!$fieldSet instanceof FieldSet) {
                        throw new UnexpectedValueException(\sprintf(
                            'Expected "%s" after flattening, got "%s".',
                            FieldSet::class,
                            get_debug_type($fieldSet),
                        ));
                    }

                    $alternatives[] = self::getContents($fieldSet);
                }

                $items[] = $alternatives;
            } else {
                throw new UnexpectedValueException(\sprintf(
                    'Unsupported item type "%s" in GROUP BY.',
                    get_debug_type($item),
                ));
            }
        }

        $this->items = $items;
    }

    /**
     * @return list<string>
     */
    private static function getContents(FieldSet $fieldSet): array
    {
        return array_map(
            static fn(Field $field): string => $field->getContent(),
            $fieldSet->toArray(),
        );
    }

    /**
     * Returns the smallest stored grouping set that contains all of the
     * given fields, or null if there is none.
     *
     * @param list<string> $fields
     * @return list<string>|null
     */
    public function select(array $fields): ?array
    {
        $remaining = array_fill_keys($fields, true);
        $result = [];

        foreach ($this->items as $alternatives) {
            // the requested fields that belong to this item

            $neededHere = [];

            foreach ($alternatives as $alternative) {
                foreach ($alternative as $field) {
                    if (isset($remaining[$field])) {
                        $neededHere[$field] = true;
                    }
                }
            }

            // the smallest alternative that contains all of them. on tie,
            // the first one wins.

            $best = null;

            foreach ($alternatives as $alternative) {
                if (array_diff_key($neededHere, array_flip($alternative)) !== []) {
                    continue;
                }

                if ($best === null || \count($alternative) < \count($best)) {
                    $best = $alternative;
                }
            }

            if ($best === null) {
                return null;
            }

            foreach ($best as $field) {
                $result[$field] = true;
            }

            $remaining = array_diff_key($remaining, $neededHere);
        }

        if ($remaining !== []) {
            return null;
        }

        return array_keys($result);
    }

    /**
     * The total number of the stored grouping sets.
     */
    public function count(): int
    {
        $count = 1;

        foreach ($this->items as $alternatives) {
            $count *= \count($alternatives);
        }

        return $count;
    }

    /**
     * Returns up to $limit of the stored grouping sets, as lists of field
     * contents.
     *
     * @param int<0,max> $limit
     * @return list<list<string>>
     */
    public function getSets(int $limit): array
    {
        $results = [[]];

        foreach ($this->items as $alternatives) {
            $newResults = [];

            foreach ($results as $result) {
                foreach ($alternatives as $alternative) {
                    $newResults[] = [...$result, ...$alternative];

                    if (\count($newResults) >= $limit) {
                        break 2;
                    }
                }
            }

            $results = $newResults;
        }

        return \array_slice($results, 0, $limit);
    }
}
