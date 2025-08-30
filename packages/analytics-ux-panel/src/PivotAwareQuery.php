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

namespace Rekalogika\Analytics\UX\PanelBundle;

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Query;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Metadata\Summary\PropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\UX\PanelBundle\Internal\FilterFactoryLocator;
use Rekalogika\Analytics\UX\PanelBundle\Internal\PivotAwareMetadataProxy;
use Rekalogika\Analytics\UX\PanelBundle\Internal\PivotTableItem;
use Rekalogika\Analytics\UX\PanelBundle\Internal\PivotTableValues;

final class PivotAwareQuery
{
    /**
     * @var list<string>
     */
    private array $values = [];

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

    private readonly Filters $filterExpressions;

    private readonly PivotAwareMetadataProxy $metadata;

    /**
     * @param array<string,mixed> $parameters
     */
    public function __construct(
        private readonly Query $query,
        SummaryMetadata $metadata,
        array $parameters,
        FilterResolver $filterResolver,
        FilterFactoryLocator $filterFactoryLocator,
    ) {
        $this->metadata = new PivotAwareMetadataProxy($metadata);

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
        // Make sure mandatory dimensions are placed as the first dimensions of
        // the row.
        //

        $mandatoryDimensions = [];

        foreach ($this->metadata->getLeafDimensions() as $name => $dimension) {
            if ($name === '@values') {
                continue;
            }

            if ($this->isDimensionMandatory($name)) {
                $mandatoryDimensions[] = $name;
            }
        }

        $this->rows = array_merge(
            $mandatoryDimensions,
            array_values(array_diff($this->rows, $mandatoryDimensions)),
        );

        $this->columns = array_values(array_diff($this->columns, $mandatoryDimensions));
        $this->filters = array_values(array_diff($this->filters, $mandatoryDimensions));

        //
        // Process filters.
        //

        $filterDimensions = array_merge(
            $this->rows,
            $this->columns,
            $this->filters,
        );

        $filters = [];

        /** @var array<string,mixed> */
        $filterExpressionsArray = $parameters['filterExpressions'] ?? [];

        foreach ($filterDimensions as $dimensionName) {
            if ($dimensionName === '@values') {
                continue;
            }

            $dimensionMetadata = $this->metadata->getLeafDimension($dimensionName);

            /** @var array<string,mixed> */
            $arrayExpression = $filterExpressionsArray[$dimensionName] ?? [];

            /** @psalm-suppress DocblockTypeContradiction */
            if (!\is_array($arrayExpression)) {
                $arrayExpression = [];
            }

            $filterSpecification = $filterResolver
                ->getFilterFactory($dimensionMetadata);

            $filterFactory = $filterFactoryLocator
                ->locate($filterSpecification->getFilterClass());

            $filters[$dimensionName] = $filterFactory->createFilter(
                dimension: $dimensionMetadata,
                inputArray: $arrayExpression,
                options: $filterSpecification->getFilterOptions(),
            );
        }

        $this->filterExpressions = new Filters($filters);

        foreach ($this->filterExpressions as $filterExpression) {
            $expression = $filterExpression->createExpression();

            if ($expression !== null) {
                $this->query->andDice($expression);
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
     * @var array<string,PivotTableItem>|null
     */
    private ?array $allChoices = null;

    /**
     * @return array<string,PivotTableItem>
     */
    private function getAllChoices(): array
    {
        if ($this->allChoices !== null) {
            return $this->allChoices;
        }

        $result = [];

        foreach ($this->metadata->getDimensions() as $name => $dimension) {
            $result[$name] = new PivotTableItem($dimension);
        }

        foreach ($this->metadata->getMeasures() as $name => $measure) {
            $result[$name] = new PivotTableItem($measure);
        }

        $result['@values'] = new PivotTableItem(new PivotTableValues());

        return $this->allChoices = $result;
    }

    public function resolve(string $key): PivotTableItem
    {
        $rootKey = explode('.', $key)[0];

        return $this->getAllChoices()[$rootKey] ?? throw new InvalidArgumentException(\sprintf('"%s" is not a valid key', $key));
    }

    /**
     * @return list<string>
     */
    private function getAllItems(): array
    {
        return [
            ...array_keys($this->metadata->getDimensions()),
            ...array_keys($this->metadata->getMeasures()),
            '@values',
        ];
    }

    //
    // Getter setter proxy methods.
    //

    private function syncRowsAndColumns(): void
    {
        $this->query->withDimensions(...array_merge($this->rows, $this->columns));
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
    public function getDimensions(): array
    {
        return array_merge($this->rows, $this->columns);
    }

    /**
     * @return list<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param list<string> $values
     */
    private function setValues(array $values): void
    {
        $this->values = $values;
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
    // Filter expressions.
    //

    public function getFilterExpressions(): Filters
    {
        return $this->filterExpressions;
    }

    //
    // Other proxy methods.
    //

    /**
     * @return array<string,PropertyMetadata>
     */
    public function getDimensionChoices(): array
    {
        return $this->metadata->getLeafDimensions();
    }

    /**
     * @return array<string,PropertyMetadata>
     */
    public function getMeasureChoices(): array
    {
        return $this->metadata->getMeasures();
    }

    public function getResult(): Result
    {
        return $this->query->getResult();
    }

    //
    // Helpers.
    //

    private function isDimensionMandatory(string $dimension): bool
    {
        // Trim dot and the string after.
        $dimension = explode('.', $dimension)[0];

        return $this->metadata->getDimension($dimension)->isMandatory();
    }

    //
    // Getters without subitems.
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

        // Items not in rows or columns.
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
            ...$this->rows,
            ...$this->columns,
            ...$this->filters,
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
