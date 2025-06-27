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

/**
 * Visitor for table and its elements
 */
interface TableVisitor
{
    public function visitTable(Table $table): void;

    public function visitTableHeader(TableHeader $tableHeader): void;

    public function visitTableBody(TableBody $tableBody): void;

    public function visitTableFooter(TableFooter $tableFooter): void;

    public function visitRow(Row $tableRow): void;

    public function visitHeaderCell(HeaderCell $headerCell): void;

    public function visitDataCell(DataCell $dataCell): void;
}
