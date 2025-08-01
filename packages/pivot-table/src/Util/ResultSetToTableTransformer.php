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

namespace Rekalogika\PivotTable\Util;

use Rekalogika\PivotTable\Contracts\Result\ResultRow;
use Rekalogika\PivotTable\Contracts\Result\ResultSet;
use Rekalogika\PivotTable\Implementation\Table\DefaultContext;
use Rekalogika\PivotTable\Implementation\Table\DefaultDataCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultHeaderCell;
use Rekalogika\PivotTable\Implementation\Table\DefaultRow;
use Rekalogika\PivotTable\Implementation\Table\DefaultRows;
use Rekalogika\PivotTable\Implementation\Table\DefaultTable;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableBody;
use Rekalogika\PivotTable\Implementation\Table\DefaultTableHeader;
use Rekalogika\PivotTable\Table\Table;

final class ResultSetToTableTransformer
{
    private DefaultTableHeader $tableHeader;
    private DefaultTableBody $tableBody;
    private DefaultContext $context;

    public static function transform(ResultSet $resultSet): Table
    {
        return (new self($resultSet))->getTable();
    }

    private function __construct(ResultSet $resultSet)
    {
        $this->context = DefaultContext::createFlat();

        $tupleCount = $this->getMaxTupleCount($resultSet);

        $rows = [];
        $thead = null;

        foreach ($resultSet as $row) {
            if ($row->getTuple()->count() !== $tupleCount) {
                continue;
            }

            $rows[] = $this->resultRowToTableRow($row);
            $thead ??= $this->getTableHeader($row);
        }

        if ($thead === null) {
            throw new \RuntimeException('ResultSet must have at least one row with a tuple.');
        }

        $this->tableBody = new DefaultTableBody(new DefaultRows($rows, $this->context), $this->context);
        $this->tableHeader = new DefaultTableHeader(new DefaultRows([$thead], $this->context), $this->context);
    }

    private function getTable(): DefaultTable
    {
        return new DefaultTable(
            [
                $this->tableHeader,
                $this->tableBody,
            ],
            $this->context,
        );
    }

    private function getMaxTupleCount(ResultSet $resultSet): int
    {
        $maxTupleCount = 0;

        foreach ($resultSet as $row) {
            $tupleCount = $row->getTuple()->count();

            if ($tupleCount > $maxTupleCount) {
                $maxTupleCount = $tupleCount;
            }
        }

        return $maxTupleCount;
    }

    private function getTableHeader(ResultRow $row): DefaultRow
    {
        $htmlRow = new DefaultRow([], $this->context);

        foreach ($row->getTuple() as $field) {
            $cell = new DefaultHeaderCell(
                name: $field->getKey(),
                content: $field->getLegend(),
                columnSpan: 1,
                rowSpan: 1,
                context: $this->context,
            );

            $htmlRow = $htmlRow->appendCell($cell);
        }

        foreach ($row->getValues() as $value) {
            $cell = new DefaultHeaderCell(
                name: $value->getKey(),
                content: $value->getLegend(),
                columnSpan: 1,
                rowSpan: 1,
                context: $this->context,
            );

            $htmlRow = $htmlRow->appendCell($cell);
        }

        return $htmlRow;
    }

    private function resultRowToTableRow(ResultRow $row): DefaultRow
    {
        $htmlRow = new DefaultRow([], $this->context);

        foreach ($row->getTuple() as $field) {
            $cell = new DefaultDataCell(
                name: $field->getKey(),
                content: $field->getItem(),
                columnSpan: 1,
                rowSpan: 1,
                context: $this->context,
            );

            $htmlRow = $htmlRow->appendCell($cell);
        }

        foreach ($row->getValues() as $value) {
            $cell = new DefaultDataCell(
                name: $value->getKey(),
                content: $value->getValue(),
                columnSpan: 1,
                rowSpan: 1,
                context: $this->context,
            );

            $htmlRow = $htmlRow->appendCell($cell);
        }

        return $htmlRow;
    }
}
