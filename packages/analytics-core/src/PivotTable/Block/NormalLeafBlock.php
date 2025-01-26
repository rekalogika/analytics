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

class NormalLeafBlock extends NodeBlock
{
    #[\Override]
    protected function createHeaderRows(): Rows
    {
        $cell = (new HeaderCell($this->getLeafNode()->getLegend()))
            ->withColumnSpan(2);
        $row = new Row([$cell]);

        return new Rows([$row]);
    }

    #[\Override]
    protected function createDataRows(): Rows
    {
        $name = new DataCell($this->getLeafNode()->getItem());
        $value = new DataCell($this->getLeafNode()->getValue());
        $row = new Row([$name, $value]);

        return new Rows([$row]);
    }
}
