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

namespace Rekalogika\Analytics\Frontend\Spreadsheet\Internal;

use Rekalogika\Analytics\Frontend\Formatter\Cellifier;
use Rekalogika\Analytics\PivotTable\Model\Property;
use Rekalogika\PivotTable\Table\Cell;
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
 * @internal
 * @implements TableVisitor<string>
 */
final readonly class SpreadsheetRendererVisitor implements TableVisitor
{
    public function __construct(private Cellifier $cellifier) {}

    private function encode(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderCell(
        Cell $cell,
        string $block,
    ): string {
        /** @psalm-suppress MixedAssignment */
        $content = $cell->getContent();
        $columnSpan = $cell->getColumnSpan();
        $rowSpan = $cell->getRowSpan();

        if ($content instanceof Property) {
            /** @psalm-suppress MixedAssignment */
            $content = $content->getContent();
        }

        $cellProperties = $this->cellifier->toCell($content);
        $content = $cellProperties->getContent();

        if ($cell instanceof HeaderCell) {
            $content = \sprintf(
                '<b>%s</b>',
                $this->encode($content),
            );
        } elseif ($cell instanceof FooterCell) {
            $content = \sprintf(
                '<i>%s</i>',
                $this->encode($content),
            );
        } else {
            $content = $this->encode($content);
        }

        return \sprintf(
            '<%s %s%s%s>%s</%s>',
            $block,
            $columnSpan > 1 ? \sprintf(' colspan="%d"', $columnSpan) : '',
            $rowSpan > 1 ? \sprintf(' rowspan="%d"', $rowSpan) : '',
            $cellProperties->getHtmlAttributesAsString(),
            $content,
            $block,
        );
    }

    #[\Override]
    public function visitTable(Table $table): mixed
    {
        $result = '<table>';

        foreach ($table as $rowGroup) {
            $result .= $rowGroup->accept($this);
        }

        return $result . '</table>';
    }

    #[\Override]
    public function visitTableHeader(TableHeader $tableHeader): mixed
    {
        $result = '<thead>';

        foreach ($tableHeader as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</thead>';
    }

    #[\Override]
    public function visitTableBody(TableBody $tableBody): mixed
    {
        $result = '<tbody>';

        foreach ($tableBody as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</tbody>';
    }

    #[\Override]
    public function visitTableFooter(TableFooter $tableFooter): mixed
    {
        $result = '<tfoot>';

        foreach ($tableFooter as $row) {
            $result .= $row->accept($this);
        }

        return $result . '</tfoot>';
    }

    #[\Override]
    public function visitRow(Row $tableRow): mixed
    {
        $result = '<tr>';

        foreach ($tableRow as $cell) {
            $result .= $cell->accept($this);
        }

        return $result . '</tr>';
    }

    #[\Override]
    public function visitHeaderCell(HeaderCell $headerCell): mixed
    {
        return $this->renderCell($headerCell, 'th');
    }

    #[\Override]
    public function visitDataCell(DataCell $dataCell): mixed
    {
        return $this->renderCell($dataCell, 'td');
    }

    #[\Override]
    public function visitFooterCell(FooterCell $footerCell): mixed
    {
        return $this->renderCell($footerCell, 'td');
    }
}
