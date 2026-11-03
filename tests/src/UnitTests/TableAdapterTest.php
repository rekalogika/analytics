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

namespace Rekalogika\Analytics\Tests\UnitTests;

use PHPUnit\Framework\TestCase;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\PivotTable\Adapter\Table\TableAdapter;

final class TableAdapterTest extends TestCase
{
    public function testLegendOfUnknownKeyIsNull(): void
    {
        // A cell without children, the table is empty and has no legend.
        $cell = $this->createStub(CubeCell::class);

        $table = new TableAdapter(
            cell: $cell,
            dimensions: ['country'],
            measures: ['count'],
        );

        $this->assertTrue($table->isEmpty());
        $this->assertNull($table->getLegend('country'));
        $this->assertNull($table->getLegend('count'));
    }
}
