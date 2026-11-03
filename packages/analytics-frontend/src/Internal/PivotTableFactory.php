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

namespace Rekalogika\Analytics\Frontend\Internal;

use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\PivotTable\Adapter\Cube\CubeAdapter;
use Rekalogika\PivotTable\HtmlTable\Table;
use Rekalogika\PivotTable\PivotTableTransformer;

/**
 * Creates the pivot table used by the HTML and spreadsheet renderers.
 *
 * @internal
 */
final readonly class PivotTableFactory
{
    private function __construct() {}

    /**
     * @param list<string> $measures
     * @param list<string> $rows
     * @param list<string> $columns
     */
    public static function create(
        CubeCell $cube,
        array $measures,
        array $rows,
        array $columns,
    ): Table {
        $cubeAdapter = CubeAdapter::adapt($cube);

        // Without measures, the special '@values' dimension has nothing to
        // pivot.
        if ($measures === []) {
            $rows = self::filterOutValues($rows);
            $columns = self::filterOutValues($columns);
        }

        $dimensions = array_merge($rows, $columns);
        // convert $dimensions to array<string,true>
        $dimensions = array_combine($dimensions, array_fill(0, \count($dimensions), true));

        return PivotTableTransformer::transform(
            cube: $cubeAdapter,
            rows: $rows,
            columns: $columns,
            measures: $measures,
            skipLegends: ['@values'],
            subtotals: $dimensions,
        );
    }

    /**
     * Filter out the special '@values' dimension from the list of dimensions.
     *
     * @param list<string> $dimensions The list of dimensions to filter.
     * @return list<string> The filtered list of dimensions without '@values'.
     */
    private static function filterOutValues(array $dimensions): array
    {
        return array_values(array_filter($dimensions, static fn($dim) => $dim !== '@values'));
    }
}
