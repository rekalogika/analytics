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

namespace Rekalogika\PivotTable\TableRenderer;

use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\FooterCell;
use Rekalogika\PivotTable\Table\HeaderCell;
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\Table;
use Rekalogika\PivotTable\Table\TableBody;
use Rekalogika\PivotTable\Table\TableFooter;
use Rekalogika\PivotTable\Table\TableHeader;
use Rekalogika\PivotTable\Table\TableVisitor;

/**
 * @api
 * @implements TableVisitor<string>
 */
class BasicTableRenderer implements TableVisitor
{
    public function getHtml(Table $table): string
    {
        return $table->accept($this);
    }

    #[\Override]
    public function visitTable(Table $table): string
    {
        $result = \sprintf('<table %s>', $this->getTableAttributes());

        foreach ($table as $rowGroup) {
            $result .= $rowGroup->accept($this);
        }

        return $result . '</table>';
    }

    #[\Override]
    public function visitTableHeader(TableHeader $tableHeader): string
    {
        $result = \sprintf('<thead %s>', $this->getTableHeaderAttributes());

        foreach ($tableHeader as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</thead>';
    }

    #[\Override]
    public function visitTableBody(TableBody $tableBody): string
    {
        $result = \sprintf('<tbody %s>', $this->getTableBodyAttributes());

        foreach ($tableBody as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</tbody>';
    }

    #[\Override]
    public function visitTableFooter(TableFooter $tableFooter): string
    {
        $result = \sprintf('<tfoot %s>', $this->getTableFooterAttributes());

        foreach ($tableFooter as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</tfoot>';
    }

    #[\Override]
    public function visitRow(Row $tableRow): string
    {
        $result = \sprintf('<tr %s>', $this->getTableRowAttributes());

        foreach ($tableRow as $cell) {
            $result .= $cell->accept($this);
        }

        return $result . '</tr>';
    }

    #[\Override]
    public function visitHeaderCell(HeaderCell $headerCell): string
    {
        $colspan = $headerCell->getColumnSpan();
        $rowspan = $headerCell->getRowSpan();

        $colspanString = $colspan > 1
            ? \sprintf(' colspan="%d"', $colspan) : '';

        $rowspanString = $rowspan > 1
            ? \sprintf(' rowspan="%d"', $rowspan) : '';

        $attributes = ' ' . $this->getHeaderCellAttributes() . $colspanString . $rowspanString;

        return \sprintf(
            '<th%s>%s</th>',
            $attributes,
            $this->visitContent($headerCell->getContent()),
        );
    }

    #[\Override]
    public function visitDataCell(DataCell $dataCell): string
    {
        $colspan = $dataCell->getColumnSpan();
        $rowspan = $dataCell->getRowSpan();

        $colspanString = $colspan > 1
            ? \sprintf(' colspan="%d"', $colspan) : '';

        $rowspanString = $rowspan > 1
            ? \sprintf(' rowspan="%d"', $rowspan) : '';

        $attributes = ' ' . $this->getDataCellAttributes() . $colspanString . $rowspanString;

        return \sprintf(
            '<td%s>%s</td>',
            $attributes,
            $this->visitContent($dataCell->getContent()),
        );
    }

    #[\Override]
    public function visitFooterCell(FooterCell $footerCell): string
    {
        $colspan = $footerCell->getColumnSpan();
        $rowspan = $footerCell->getRowSpan();

        $colspanString = $colspan > 1
            ? \sprintf(' colspan="%d"', $colspan) : '';

        $rowspanString = $rowspan > 1
            ? \sprintf(' rowspan="%d"', $rowspan) : '';

        $attributes = ' ' . $this->getDataCellAttributes() . $colspanString . $rowspanString;

        return \sprintf(
            '<td%s>%s</td>',
            $attributes,
            $this->visitContent($footerCell->getContent()),
        );
    }

    public function visitContent(mixed $content): string
    {
        if (\is_string($content) || \is_int($content) || \is_float($content)) {
            $result = (string) $content;
        } elseif ($content instanceof \BackedEnum) {
            $result = (string) $content->value;
        } elseif ($content instanceof \UnitEnum) {
            $result = $content->name;
        } elseif ($content instanceof \Stringable) {
            $result = (string) $content;
        } elseif (\is_bool($content)) {
            $result = $content ? 'true' : 'false';
        } elseif (\is_array($content)) {
            $result = implode(', ', array_map(fn($item): string => $this->visitContent($item), $content));
        } else {
            $result = get_debug_type($content);
        }

        return htmlspecialchars($result);
    }

    protected function getTableAttributes(): string
    {
        return '';
    }

    protected function getTableHeaderAttributes(): string
    {
        return '';
    }

    protected function getTableBodyAttributes(): string
    {
        return '';
    }

    protected function getTableFooterAttributes(): string
    {
        return '';
    }

    protected function getTableRowAttributes(): string
    {
        return '';
    }

    protected function getDataCellAttributes(): string
    {
        return '';
    }

    protected function getHeaderCellAttributes(): string
    {
        return '';
    }
}
