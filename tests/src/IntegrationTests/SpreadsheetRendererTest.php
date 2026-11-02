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
    private function getResult(array $dimensions): CubeCell
    {
        return static::getContainer()
            ->get(SummaryManager::class)
            ->createQuery()
            ->from(OrderSummary::class)
            ->withDimensions(...$dimensions)
            ->getResult();
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
}
