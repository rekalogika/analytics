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
use Rekalogika\DoctrineAdvancedGroupBy\Field;
use Rekalogika\DoctrineAdvancedGroupBy\FieldSet;
use Rekalogika\DoctrineAdvancedGroupBy\GroupingSet;

/**
 * Stores only the explicitly listed grouping sets, instead of every
 * combination of the children.
 *
 * Each set is a list of children names. A child can be a plain dimension or a
 * dimension group. A dimension group keeps its own semantics: if it is a
 * rollup of year, month and day, then the set `['time', 'customer']` produces
 * the sets (customer), (year, customer), (year, month, customer), and (year,
 * month, day, customer).
 *
 * An empty set stores the grand total.
 *
 * Usually used as the grouping strategy of a summary class, in which case the
 * children are the root dimensions of the summary:
 *
 * ```php
 * #[Summary(
 *     sourceClass: Order::class,
 *     groupingStrategy: new ExplicitGroupingSetsStrategy([
 *         ['time', 'customer', 'product'],
 *         ['customer'],
 *     ]),
 * )]
 * ```
 *
 * A query can select any subset of a stored set. The query engine picks the
 * smallest stored set that contains every dimension involved in the query,
 * and aggregates it at query time.
 */
final readonly class ExplicitGroupingSetsStrategy implements GroupingStrategy
{
    /**
     * @param list<list<string>> $sets The grouping sets, each one is a list
     * of children names.
     */
    public function __construct(
        private array $sets,
    ) {
        if ($sets === []) {
            throw new InvalidArgumentException(\sprintf(
                '"%s" requires at least one grouping set.',
                self::class,
            ));
        }

        foreach ($sets as $set) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!\is_array($set) || !array_is_list($set)) {
                throw new InvalidArgumentException(\sprintf(
                    'Each grouping set in "%s" must be a list of names.',
                    self::class,
                ));
            }

            foreach ($set as $name) {
                /** @psalm-suppress DocblockTypeContradiction */
                if (!\is_string($name)) {
                    throw new InvalidArgumentException(\sprintf(
                        'Each member of a grouping set in "%s" must be a string, "%s" given.',
                        self::class,
                        get_debug_type($name),
                    ));
                }
            }

            if (\count($set) !== \count(array_unique($set))) {
                throw new InvalidArgumentException(\sprintf(
                    'Grouping set [%s] in "%s" contains duplicate names.',
                    implode(', ', $set),
                    self::class,
                ));
            }
        }
    }

    /**
     * @return list<list<string>>
     */
    public function getSets(): array
    {
        return $this->sets;
    }

    #[\Override]
    public function getGroupByExpression(
        GroupByExpressions $fields,
    ): GroupingSet {
        $result = [];

        foreach ($this->sets as $set) {
            // each member contributes a list of alternative field sets

            /** @var list<list<list<Field>>> */
            $members = [];

            foreach ($set as $name) {
                $members[] = $this->getAlternatives($fields, $name);
            }

            foreach (self::crossProduct($members) as $combination) {
                // deduplicate regardless of the order of the fields. a
                // duplicate grouping set would produce duplicate rows in the
                // summary table.

                $contents = array_map(
                    static fn(Field $field): string => $field->getContent(),
                    $combination,
                );

                sort($contents);
                $key = implode("\0", $contents);

                $result[$key] ??= new FieldSet(...$combination);
            }
        }

        return new GroupingSet(...array_values($result));
    }

    /**
     * @return list<list<Field>>
     */
    private function getAlternatives(
        GroupByExpressions $fields,
        string $name,
    ): array {
        $expression = $fields->get($name);

        if ($expression === null) {
            throw new InvalidArgumentException(\sprintf(
                'Unknown dimension "%s" in a grouping set of "%s". Available dimensions: %s',
                $name,
                self::class,
                implode(', ', array_keys(iterator_to_array($fields))),
            ));
        }

        if ($expression instanceof Field) {
            return [[$expression]];
        }

        if ($expression instanceof FieldSet) {
            return [$expression->toArray()];
        }

        // Cube, RollUp, or GroupingSet

        $alternatives = [];

        foreach ($expression->flatten() as $fieldSet) {
            if (!$fieldSet instanceof FieldSet) {
                throw new InvalidArgumentException(\sprintf(
                    'Expected "%s" after flattening "%s", got "%s".',
                    FieldSet::class,
                    $name,
                    get_debug_type($fieldSet),
                ));
            }

            $alternatives[] = $fieldSet->toArray();
        }

        return $alternatives;
    }

    /**
     * @param list<list<list<Field>>> $members
     * @return list<list<Field>>
     */
    private static function crossProduct(array $members): array
    {
        $results = [[]];

        foreach ($members as $alternatives) {
            $newResults = [];

            foreach ($results as $result) {
                foreach ($alternatives as $alternative) {
                    $newResults[] = [...$result, ...$alternative];
                }
            }

            $results = $newResults;
        }

        return $results;
    }
}
