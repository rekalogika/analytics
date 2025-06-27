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

namespace Rekalogika\PivotTable\Table;

abstract class BaseTableVisitor implements TableVisitor
{
    #[\Override]
    public function visitTable(Table $table): void
    {
        foreach ($table->getRows() as $rowGroup) {
            $rowGroup->accept($this);
        }
    }

    #[\Override]
    public function visitTableHeader(TableHeader $tableHeader): void
    {
        foreach ($tableHeader as $row) {
            $row->accept($this);
        }
    }

    #[\Override]
    public function visitTableBody(TableBody $tableBody): void
    {
        foreach ($tableBody as $row) {
            $row->accept($this);
        }
    }

    #[\Override]
    public function visitTableFooter(TableFooter $tableFooter): void
    {
        foreach ($tableFooter as $row) {
            $row->accept($this);
        }
    }

    #[\Override]
    public function visitRow(Row $tableRow): void
    {
        foreach ($tableRow as $cell) {
            $cell->accept($this);
        }
    }

    #[\Override]
    public function visitHeaderCell(HeaderCell $headerCell): void
    {
        // Default implementation does nothing
    }

    #[\Override]
    public function visitDataCell(DataCell $dataCell): void
    {
        // Default implementation does nothing
    }
}
