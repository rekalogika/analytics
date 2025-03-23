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

namespace Rekalogika\PivotTable\Block;

use Rekalogika\PivotTable\Table\ContentType;
use Rekalogika\PivotTable\Table\DataCell;
use Rekalogika\PivotTable\Table\HeaderCell;
use Rekalogika\PivotTable\Table\Row;
use Rekalogika\PivotTable\Table\Rows;

final class NormalLeafBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        $cell = new HeaderCell(
            type: ContentType::Legend,
            key: $this->getLeafNode()->getKey(),
            content: $this->getLeafNode()->getLegend(),
            treeNode: $this->getLeafNode(),
            columnSpan: 2,
        );

        $row = new Row([$cell]);

        return new Rows([$row]);
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        $name = new DataCell(
            type: ContentType::Item,
            key: $this->getLeafNode()->getKey(),
            content: $this->getLeafNode()->getItem(),
            treeNode: $this->getLeafNode(),
        );

        $value = new DataCell(
            type: ContentType::Value,
            key: $this->getLeafNode()->getKey(),
            content: $this->getLeafNode()->getValue(),
            treeNode: $this->getLeafNode(),
        );

        $row = new Row([$name, $value]);

        return new Rows([$row]);
    }
}
