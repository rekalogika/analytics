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

use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Rekalogika\Analytics\Bundle\Formatter\Cellifier;
use Rekalogika\Analytics\Bundle\UI\Implementation\SpreadSheetRendererVisitor;
use Rekalogika\Analytics\Contracts\Result\Result;
use Rekalogika\Analytics\PivotTable\Adapter\PivotTableAdapter;
use Rekalogika\PivotTable\PivotTableTransformer;

final readonly class SpreadsheetRenderer
{
    private SpreadSheetRendererVisitor $visitor;

    public function __construct(
        Cellifier $cellifier,
    ) {
        $this->visitor = new SpreadSheetRendererVisitor($cellifier);
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function createSpreadsheet(
        Result $result,
        array $pivotedDimensions = [],
    ): Spreadsheet {
        $treeResult = $result->getTree();
        $pivotTable = PivotTableAdapter::adapt($treeResult);

        $table = PivotTableTransformer::transformTreeNodeToPivotTable(
            treeNode: $pivotTable,
            pivotedNodes: $pivotedDimensions,
            superfluousLegends: ['@values'],
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
