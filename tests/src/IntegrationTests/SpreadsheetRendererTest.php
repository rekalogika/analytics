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

namespace Rekalogika\Analytics\Tests\IntegrationTests;

use Doctrine\Common\Collections\Criteria;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\SummaryManager;
use Rekalogika\Analytics\Frontend\Spreadsheet\SpreadsheetRenderer;
use Rekalogika\Analytics\Tests\App\Entity\OrderSummary;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SpreadsheetRendererTest extends KernelTestCase
{
    /**
     * @param list<string> $dimensions
     */
    private function getResult(array $dimensions, bool $empty = false): CubeCell
    {
        $query = static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions(...$dimensions);

        if ($empty) {
            $query->dice(Criteria::expr()->eq('time.civil.year', 1900));
        }

        return $query->getResult();
    }

    private function getRenderer(): SpreadsheetRenderer
    {
        return static::getContainer()->get(SpreadsheetRenderer::class);
    }

    public function testNumberIsNumericCell(): void
    {
        $spreadsheet = $this->getRenderer()->render(
            cell: $this->getResult(['customerCountry']),
            dimensions: ['customerCountry'],
            measures: ['count'],
        );

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $this->assertGreaterThan(1, $highestRow);

        // row 1 is the header
        for ($row = 2; $row <= $highestRow; $row++) {
            $cell = $sheet->getCell('B' . $row);

            $this->assertSame(DataType::TYPE_NUMERIC, $cell->getDataType());
            $this->assertIsNumeric($cell->getValue());
        }
    }

    public function testEmptyResult(): void
    {
        $spreadsheet = $this->getRenderer()->render(
            cell: $this->getResult(['customerCountry'], empty: true),
            dimensions: ['customerCountry'],
            measures: ['count'],
        );

        $this->assertSame(1, $spreadsheet->getActiveSheet()->getHighestDataRow());
        $this->assertNull($spreadsheet->getActiveSheet()->getCell('A1')->getValue());
    }

    public function testEmptyResultInPivotTable(): void
    {
        $spreadsheet = $this->getRenderer()->renderPivotTable(
            cube: $this->getResult(['customerCountry'], empty: true),
            measures: ['count'],
            rows: ['customerCountry'],
            columns: ['@values'],
        );

        $this->assertSame('Pivot Table', $spreadsheet->getActiveSheet()->getTitle());
    }

    public function testPivotTableWithoutMeasures(): void
    {
        $spreadsheet = $this->getRenderer()->renderPivotTable(
            cube: $this->getResult(['customerCountry', 'customerGender']),
            measures: [],
            rows: ['customerCountry'],
            columns: ['customerGender', '@values'],
        );

        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Pivot Table', $sheet->getTitle());
        $this->assertGreaterThan(1, $sheet->getHighestDataRow());
    }

    public function testPivotTableHasNoAutoFilter(): void
    {
        $spreadsheet = $this->getRenderer()->renderPivotTable(
            cube: $this->getResult(['customerCountry', 'customerGender']),
            measures: ['count'],
            rows: ['customerCountry'],
            columns: ['customerGender', '@values'],
        );

        $this->assertSame('', $spreadsheet->getActiveSheet()->getAutoFilter()->getRange());
    }

    public function testTableHasAutoFilter(): void
    {
        $spreadsheet = $this->getRenderer()->render(
            cell: $this->getResult(['customerCountry']),
            dimensions: ['customerCountry'],
            measures: ['count'],
        );

        $this->assertNotSame('', $spreadsheet->getActiveSheet()->getAutoFilter()->getRange());
    }
}
