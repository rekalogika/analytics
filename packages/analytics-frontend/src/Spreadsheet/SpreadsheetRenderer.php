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

namespace Rekalogika\Analytics\Frontend\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Spreadsheet\Internal\SpreadsheetRendererVisitor;
use Rekalogika\Analytics\PivotTable\Adapter\Cube\CubeAdapter;
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Util\TableToHtmlTableTransformer;

final readonly class SpreadsheetRenderer
{
    private SpreadsheetRendererVisitor $visitor;

    public function __construct(Cellifier $cellifier)
    {
        $this->visitor = new SpreadsheetRendererVisitor($cellifier);
    }

    /**
     * @param CubeCell $cell The root cell that contains the table data.
     * @param list<string> $dimensions The dimensions that will be displayed in
     * the table.
     * @param list<string> $measures The measures that will be displayed in the
     * table.
     */
    public function render(
        CubeCell $cell,
        array $dimensions,
        array $measures,
    ): Spreadsheet {
        $table = new TableAdapter(
            cell: $cell,
            dimensions: $dimensions,
            measures: $measures,
        );

        $table = TableToHtmlTableTransformer::transform($table);

        $html = $this->visitor->visitTable($table);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($html);

        foreach ($spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $spreadsheet->getActiveSheet()->setAutoFilter(
            $spreadsheet->getActiveSheet()->calculateWorksheetDimension(),
        );

        $spreadsheet->getActiveSheet()->setTitle('Pivot Table');

        return $spreadsheet;
    }

    /**
     * @param list<string> $measures
     * @param list<string> $rows
     * @param list<string> $columns
     */
    public function renderPivotTable(
        Result $result,
        array $measures,
        array $rows,
        array $columns,
    ): Spreadsheet {
        $cubeAdapter = CubeAdapter::adapt($result->getCube());

        $dimensions = array_merge($rows, $columns);
        // convert $dimensions to array<string,true>
        $dimensions = array_combine($dimensions, array_fill(0, \count($dimensions), true));

        $table = PivotTableTransformer::transform(
            cube: $cubeAdapter,
            rows: $rows,
            columns: $columns,
            measures: $measures,
            skipLegends: ['@values'],
            subtotals: $dimensions,
        );

        $html = $this->visitor->visitTable($table);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($html);

        foreach ($spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        $spreadsheet->getActiveSheet()->setAutoFilter(
            $spreadsheet->getActiveSheet()->calculateWorksheetDimension(),
        );

        $spreadsheet->getActiveSheet()->setTitle('Pivot Table');

        return $spreadsheet;
    }
}
