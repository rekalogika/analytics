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
use Rekalogika\Analytics\Contracts\Result;
use Twig\Environment;

final readonly class SpreadsheetRenderer
{
    public function __construct(
        private Environment $twig,
        private string $theme = '@RekalogikaAnalytics/spreadsheet_renderer.html.twig',
    ) {}

    private function createPivotTableRenderer(): PivotTableRenderer
    {
        return new PivotTableRenderer(
            twig: $this->twig,
            theme: $this->theme,
        );
    }

    /**
     * @param list<string> $pivotedDimensions
     */
    public function createSpreadsheet(
        Result $result,
        array $pivotedDimensions = [],
    ): Spreadsheet {
        $html = $this->createPivotTableRenderer()->createPivotTable(
            result: $result,
            pivotedDimensions: $pivotedDimensions,
        );

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
