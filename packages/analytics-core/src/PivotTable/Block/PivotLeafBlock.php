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

namespace Rekalogika\Analytics\PivotTable\Block;

use Rekalogika\Analytics\PivotTable\Table\DataCell;
use Rekalogika\Analytics\PivotTable\Table\HeaderCell;
use Rekalogika\Analytics\PivotTable\Table\Row;
use Rekalogika\Analytics\PivotTable\Table\Rows;

final class PivotLeafBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        if (
            $this->getLeafNode()->getKey() === '@values'
        ) {
            $cell = new HeaderCell($this->getLeafNode()->getItem());
        } else {
            $cell = new DataCell($this->getLeafNode()->getItem());
        }

        $row = new Row([$cell]);

        return new Rows([$row]);
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        $cell = new DataCell($this->getLeafNode()->getValue());
        $row = new Row([$cell]);

        return new Rows([$row]);
    }
}
