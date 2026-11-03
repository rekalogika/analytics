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
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Internal\PivotTableFactory;
use Rekalogika\Analytics\Frontend\Spreadsheet\Internal\SpreadsheetRendererVisitor;
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;
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

        // the transformer does not accept a table without rows
        if ($table->isEmpty()) {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getActiveSheet()->setTitle('Pivot Table');

            return $spreadsheet;
        }

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
        CubeCell $cube,
        array $measures,
        array $rows,
        array $columns,
    ): Spreadsheet {
        $table = PivotTableFactory::create(
            cube: $cube,
            measures: $measures,
            rows: $rows,
            columns: $columns,
        );

        $html = $this->visitor->visitTable($table);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($html);

        foreach ($spreadsheet->getActiveSheet()->getColumnIterator() as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // No autofilter: the pivot table has multiple header rows and
        // subtotal rows, which an autofilter would treat as data.

        $spreadsheet->getActiveSheet()->setTitle('Pivot Table');

        return $spreadsheet;
    }
}
