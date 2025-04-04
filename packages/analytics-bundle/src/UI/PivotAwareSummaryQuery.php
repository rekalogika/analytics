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

namespace Rekalogika\Analytics\Bundle\UI;

use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\SummaryManager\Field;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

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

    private Filters $filterExpressions;

    /**
     * @param array<string,mixed> $parameters
     */
    public function __construct(
        private readonly SummaryQuery $summaryQuery,
        array $parameters,
        FilterFactory $filterFactory,
    ) {
        $this->setRows(
            $this->getListOfStringFromArray($parameters['rows'] ?? null),
        );

        $this->setColumns(
            $this->getListOfStringFromArray($parameters['columns'] ?? null),
        );

        $this->setValues(
            $this->getListOfStringFromArray($parameters['values'] ?? null),
        );

        $this->setFilters(
            $this->getListOfStringFromArray($parameters['filters'] ?? null),
        );

        //
        // process filters
        //

        $filterDimensions = array_merge(
            $this->getRows(),
            $this->getColumns(),
            $this->getFilters(),
        );

        $filterDimensions = array_values(array_unique(array_filter(
            $filterDimensions,
            static fn(string $dimension): bool => $dimension !== '@values',
        )));

        /**
         * @psalm-suppress MixedArgument
         */
        $this->filterExpressions = new Filters(
            summaryClass: $this->summaryQuery->getClass(),
            dimensions: $filterDimensions,
            // @phpstan-ignore argument.type
            arrayExpressions: $parameters['filterExpressions'] ?? [],
            filterFactory: $filterFactory,
        );

        $unusedFilters = [];

        foreach ($this->filterExpressions as $filterExpression) {
            $expression = $filterExpression->createExpression();

            if ($expression !== null) {
                $this->summaryQuery->andWhere($expression);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function getListOfStringFromArray(mixed $maybeArray): array
    {
        if (!\is_array($maybeArray)) {
            return [];
        }

        $result = [];

        /** @var mixed $item */
        foreach ($maybeArray as $item) {
            if (\is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
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

    public function getFilterExpressions(): Filters
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
        $columns = $this->getColumnsWithoutSubitems();

        if (
            !\in_array('@values', $this->columns, true)
            && !\in_array('@values', $this->rows, true)
        ) {
            $columns[] = '@values';
        }

        // items not in rows or columns
        return array_values(array_diff(
            $this->getAllItems(),
            $this->getRowsWithoutSubItems(),
            $columns,
            $this->getValues(),
            $this->getFiltersWithoutSubitems(),
        ));
    }

    public function getSelectedSubitem(string $item): ?string
    {
        $withSubItems = [
            ...$this->getRows(),
            ...$this->getColumns(),
            ...$this->getFilters(),
        ];

        foreach ($withSubItems as $withSubItem) {
            if (!str_contains($withSubItem, '.')) {
                continue;
            }

            /** @psalm-suppress PossiblyUndefinedArrayOffset */
            [$dimension, $subItem] = explode('.', $withSubItem, 2);

            if ($dimension === $item) {
                return $subItem;
            }
        }

        return null;
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
}
