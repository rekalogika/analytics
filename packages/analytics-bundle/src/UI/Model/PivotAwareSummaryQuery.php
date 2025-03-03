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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Doctrine\Common\Collections\Criteria;
use Rekalogika\Analytics\Query\Result;
use Rekalogika\Analytics\SummaryManager\Field;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PivotAwareSummaryQuery
{
    /**
     * @var list<string>
     */
    private array $rows = [];

    /**
     * @var list<string>
     */
    private array $columns = [];

    /**
     * @var list<string>
     */
    private array $filters = [];

    private FilterExpressions $filterExpressions;

    /**
     * @param array<string,mixed> $parameters
     */
    public function __construct(
        private readonly SummaryQuery $summaryQuery,
        array $parameters,
        private readonly TranslatorInterface $translator,
    ) {
        if (isset($parameters['rows'])) {
            /**
             * @psalm-suppress MixedArgument
             * @phpstan-ignore argument.type
             */
            $this->setRows($parameters['rows']);
        }

        if (isset($parameters['columns'])) {
            /**
             * @psalm-suppress MixedArgument
             * @phpstan-ignore argument.type
             */
            $this->setColumns($parameters['columns']);
        }

        if (isset($parameters['values'])) {
            /**
             * @psalm-suppress MixedArgument
             * @phpstan-ignore argument.type
             */
            $this->setValues($parameters['values']);
        }

        if (isset($parameters['filters'])) {
            /**
             * @psalm-suppress MixedArgument
             * @phpstan-ignore argument.type
             */
            $this->setFilters($parameters['filters']);
        }

        /**
         * @psalm-suppress MixedArgument
         */
        $this->filterExpressions = new FilterExpressions(
            summaryClass: $summaryQuery->getClass(),
            dimensions: $this->getFilters(),
            // @phpstan-ignore argument.type
            arrayExpressions: $parameters['filterExpressions'] ?? [],
            query: $summaryQuery,
        );

        foreach ($this->filterExpressions as $dimension => $filter) {
            /** @psalm-suppress ImpureMethodCall */
            $this->summaryQuery
                ->andWhere(Criteria::expr()->in($dimension, $filter->getValues()));
        }
    }

    /**
     * @var array<string,array{key:string,label:string|\Stringable|TranslatableInterface,choices:array<string,string|TranslatableInterface>|null,type?:'dimension'|'measure'|'values'}>|null
     */
    private ?array $allChoices = null;

    /**
     * @return array<string,array{key:string,label:string|\Stringable|TranslatableInterface,choices:array<string,string|TranslatableInterface>|null,type?:'dimension'|'measure'|'values'}>
     */
    private function getAllChoices(): array
    {
        if ($this->allChoices !== null) {
            return $this->allChoices;
        }

        $result = [];

        foreach ($this->summaryQuery->getHierarchicalDimensionChoices() as $key => $dimension) {
            $result[$key]['key'] = $key;
            $result[$key]['type'] = 'dimension';
            $result[$key]['choices'] = null;

            if (is_iterable($dimension)) {
                /** @var iterable<string,string|TranslatableInterface> $dimension */
                foreach ($dimension as $childKey => $child) {
                    $result[$key]['choices'][$childKey] = $child;
                }
            }

            if ($dimension instanceof TranslatableInterface) {
                $result[$key]['label'] = $dimension;
            } elseif ($dimension instanceof \Stringable) {
                $result[$key]['label'] = (string) $dimension;
            } else {
                $result[$key]['label'] = '(unknown)';
            }
        }

        foreach ($this->summaryQuery->getMeasureChoices() as $key => $measure) {
            $result[$key] = [
                'key' => $key,
                'type' => 'measure',
                'label' => $measure,
                'choices' => null,
            ];
        }

        $result['@values'] = [
            'key' => '@values',
            'type' => 'values',
            'label' => new TranslatableMessage('Values'),
            'choices' => null,
        ];

        return $this->allChoices = $result;
    }

    /**
     * @return array{key:string,label:string|\Stringable|TranslatableInterface,choices:?array<string,string|TranslatableInterface>,type?:'dimension'|'measure'|'values'}
     */
    public function resolve(string $key): array
    {
        $rootKey = explode('.', $key)[0];

        return $this->getAllChoices()[$rootKey] ?? throw new \InvalidArgumentException(\sprintf('"%s" is not a valid key', $key));
    }

    /**
     * @return list<string>
     */
    private function getAllItems(): array
    {
        return [
            ...array_keys($this->summaryQuery->getHierarchicalDimensionChoices()),
            ...array_keys($this->summaryQuery->getMeasureChoices()),
            '@values',
        ];
    }

    //
    // getter setter proxy methods
    //

    private function syncRowsAndColumns(): void
    {
        $this->summaryQuery->groupBy(...array_merge($this->rows, $this->columns));
    }

    /**
     * @return list<string>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param list<string> $rows
     */
    private function setRows(array $rows): void
    {
        $this->rows = $rows;
        $this->syncRowsAndColumns();
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param list<string> $columns
     */
    private function setColumns(array $columns): void
    {
        $this->columns = $columns;
        $this->syncRowsAndColumns();
    }

    /**
     * @return list<string>
     */
    public function getValues(): array
    {
        return $this->summaryQuery->getSelect();
    }

    /**
     * @param list<string> $values
     */
    private function setValues(array $values): void
    {
        $this->summaryQuery->select(...$values);
    }

    /**
     * @return list<string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param list<string> $filters
     */
    private function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    //
    // filter expressions
    //

    public function getFilterExpressions(): FilterExpressions
    {
        return $this->filterExpressions;
    }

    //
    // other proxy methods
    //

    /**
     * @return array<string,Field>
     */
    public function getDimensionChoices(): array
    {
        return $this->summaryQuery->getDimensionChoices();
    }

    /**
     * @return array<string,Field>
     */
    public function getMeasureChoices(): array
    {
        return $this->summaryQuery->getMeasureChoices();
    }

    public function getResult(): Result
    {
        return $this->summaryQuery->getResult();
    }

    //
    // helpers
    //

    /**
     * @return list<string> $items
     */
    public function getPivotedDimensions(): array
    {
        return $this->columns;
    }

    //
    // getters without subitems
    //

    /**
     * @return list<string>
     */
    public function getAvailableWithoutSubItems(): array
    {
        $columns = $this->columns;

        if (
            !\in_array('@values', $this->columns, true)
            && !\in_array('@values', $this->rows, true)
        ) {
            $columns[] = '@values';
        }

        // items not in rows or columns
        return array_values(array_diff(
            $this->getAllItems(),
            $this->rows,
            $columns,
            $this->getValues(),
        ));
    }

    /**
     * Row items without subitems
     *
     * @return list<string>
     */
    public function getRowsWithoutSubItems(): array
    {
        $items = [];

        foreach ($this->rows as $dimension) {
            $items[] = explode('.', $dimension)[0];
        }

        return $items;
    }

    /**
     * Column items without subitems
     *
     * @return list<string>
     */
    public function getColumnsWithoutSubitems(): array
    {
        $items = [];

        foreach ($this->columns as $dimension) {
            $items[] = explode('.', $dimension)[0];
        }

        if (
            !\in_array('@values', $this->columns, true)
            && !\in_array('@values', $this->rows, true)
        ) {
            $items[] = '@values';
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function getValuesWithoutSubitems(): array
    {
        $items = [];

        foreach ($this->getValues() as $measure) {
            $items[] = explode('.', $measure)[0];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function getFiltersWithoutSubitems(): array
    {
        $items = [];

        foreach ($this->filters as $filter) {
            $items[] = explode('.', $filter)[0];
        }

        return $items;
    }

    //
    // distinct values
    //

    /**
     * @param string $dimension
     * @return null|iterable<Choice>
     */
    public function getChoices(string $dimension): null|iterable
    {
        if ($dimension === '@values') {
            return null;
        }

        $dimensionField = $this->summaryQuery->getDimensionChoices()[$dimension]
            ?? throw new \InvalidArgumentException(\sprintf('Dimension "%s" not found', $dimension));

        $choices = $this->summaryQuery
            ->getDistinctValues($this->summaryQuery->getClass(), $dimension);

        if ($choices === null) {
            return null;
        }

        $choices2 = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($choices as $id => $value) {
            $choices2[] = new Choice(
                id: $id,
                value: $value,
                label: $this->getChoiceLabel($value),
            );
        }

        return new Choices(
            label: $dimensionField,
            choices: $choices2,
        );
    }

    public function getIdToChoice(string $dimension, string $id): mixed
    {
        return $this->summaryQuery
            ->getValueFromId($this->summaryQuery->getClass(), $dimension, $id);
    }

    public function getChoiceLabel(mixed $choice): string
    {
        if ($choice instanceof TranslatableInterface) {
            return $choice->trans($this->translator);
        }

        if (
            $choice instanceof \Stringable
            || \is_string($choice)
            || \is_int($choice)
            || \is_float($choice)
        ) {
            return (string) $choice;
        }

        if ($choice instanceof \BackedEnum) {
            return (string) $choice->value;
        }

        if ($choice instanceof \UnitEnum) {
            return $choice->name;
        }

        if (\is_object($choice)) {
            return \sprintf('%s:%s', $choice::class, spl_object_id($choice));
        }

        if (\is_bool($choice)) {
            $choice = $choice ? new TranslatableMessage('Yes') : new TranslatableMessage('No');

            return $choice->trans($this->translator);
        }

        return get_debug_type($choice);
    }
}
