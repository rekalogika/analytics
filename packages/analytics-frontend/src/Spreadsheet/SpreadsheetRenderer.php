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
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\Frontend\Spreadsheet\Internal\SpreadsheetRendererVisitor;
use Rekalogika\Analytics\Frontend\Util\HardcodedSubtotalDescriptionResolver;
use Rekalogika\Analytics\PivotTable\Adapter\Cube\CubeAdapter;
use Rekalogika\Analytics\PivotTable\Adapter\ResultSet\TableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;
use Rekalogika\PivotTable\Util\ResultSetToTableTransformer;

final readonly class SpreadsheetRenderer
{
    private SpreadsheetRendererVisitor $visitor;

    public function __construct(
        Cellifier $cellifier,
    ) {
        $this->visitor = new SpreadsheetRendererVisitor($cellifier);
    }

    public function render(Result $result): Spreadsheet
    {
        $table = new TableAdapter($result->getTable());
        $table = ResultSetToTableTransformer::transform($table);

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
     * @param list<string> $pivotedDimensions
     */
    public function renderPivotTable(
        Result $result,
        array $pivotedDimensions = [],
    ): Spreadsheet {
        $dimensions = $result->getDimensionality();
        $cube = CubeAdapter::adapt($result->getCube());

        $table = PivotTableTransformer::transform(
            cube: $cube,
            subtotalDescriptionResolver: new HardcodedSubtotalDescriptionResolver(),
            pivotedNodes: $pivotedDimensions,
            skipLegends: ['@values'],
            createSubtotals: $dimensions,
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
