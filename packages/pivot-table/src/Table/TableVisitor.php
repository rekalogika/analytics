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
 *
 * @template T
 */
interface TableVisitor
{
    /**
     * @return T
     */
    public function visitTable(Table $table): mixed;

    /**
     * @return T
     */
    public function visitTableHeader(TableHeader $tableHeader): mixed;

    /**
     * @return T
     */
    public function visitTableBody(TableBody $tableBody): mixed;

    /**
     * @return T
     */
    public function visitTableFooter(TableFooter $tableFooter): mixed;

    /**
     * @return T
     */
    public function visitRow(Row $tableRow): mixed;

    /**
     * @return T
     */
    public function visitHeaderCell(HeaderCell $headerCell): mixed;

    /**
     * @return T
     */
    public function visitDataCell(DataCell $dataCell): mixed;

    /**
     * @return T
     */
    public function visitFooterCell(FooterCell $footerCell): mixed;
}
