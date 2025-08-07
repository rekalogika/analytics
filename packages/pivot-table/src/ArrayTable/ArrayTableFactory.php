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

namespace Rekalogika\PivotTable\ArrayTable;

final class ArrayTableFactory
{
    /**
     * @param list<string> $dimensionFields
     * @param list<string> $measureFields
     * @param array<string,mixed> $legends Key is any dimension and measure
     * field, value is the legend value. Key can also be `@values` to indicate
     * the legend of the measure dimension.
     *
     */
    public function __construct(
        private readonly array $dimensionFields,
        private readonly array $measureFields,
        private readonly string $groupingField,
        private readonly array $legends,
    ) {}

    /**
     * @param list<array<string,mixed>> $input
     */
    public function create(array $input): ArrayTable
    {
        return new ArrayTable(
            rows: $this->createRows($input),
            legends: $this->legends,
        );
    }

    /**
     * @return int<1,max>
     */
    private function getGroupingBit(string $dimensionName): int
    {
        $pos = array_search($dimensionName, $this->dimensionFields, true);

        if ($pos === false) {
            throw new \InvalidArgumentException("Dimension field not found: $dimensionName");
        }

        $count = \count($this->dimensionFields);

        if ($pos >= $count) {
            throw new \InvalidArgumentException("Position out of range for dimension field: $dimensionName");
        }

        /** @var int<1,max> */
        return $count - $pos;
    }

    private function isGrouping(string $dimensionName, int $grouping): bool
    {
        $bit = $this->getGroupingBit($dimensionName);

        return ($grouping & (1 << ($bit - 1))) !== 0;
    }

    /**
     * @param list<array<string,mixed>> $input
     * @return iterable<ArrayRow>
     */
    public function createRows(array $input): iterable
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($input as $row) {
            /** @psalm-suppress MixedArgument */
            yield $this->createRow($row);
        }
    }

    /**
     * @param array<string,mixed> $input
     */
    public function createRow(array $input): ArrayRow
    {
        $grouping = $input[$this->groupingField]
            ?? throw new \InvalidArgumentException("Missing grouping field: {$this->groupingField}");

        if (!\is_int($grouping)) {
            throw new \InvalidArgumentException("Grouping field must be an integer: {$this->groupingField}");
        }

        $dimensions = [];

        foreach ($this->dimensionFields as $field) {
            if ($this->isGrouping($field, $grouping)) {
                continue; // Skip grouping fields
            }

            if (!\array_key_exists($field, $input)) {
                throw new \InvalidArgumentException("Missing dimension field: $field");
            }

            /** @psalm-suppress MixedAssignment */
            $dimensions[$field] = $input[$field];
        }

        $measures = [];

        foreach ($this->measureFields as $field) {
            /** @psalm-suppress MixedAssignment */
            $measures[$field] = $input[$field]
                ?? throw new \InvalidArgumentException("Missing measure field: $field");
        }

        return new ArrayRow(
            dimensions: $dimensions,
            measures: $measures,
        );
    }
}
