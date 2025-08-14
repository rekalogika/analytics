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

namespace Rekalogika\Analytics\PivotTable\Adapter\Table;

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\PivotTable\Model\Table\TableMember;
use Rekalogika\Analytics\PivotTable\Model\Table\TableValue;
use Rekalogika\PivotTable\Contracts\Table\Row as PivotTableRow;

final readonly class RowAdapter implements PivotTableRow
{
    public function __construct(
        private Row $row,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        foreach ($this->row->getTuple() as $name => $dimension) {
            yield $name => new TableMember($dimension->getMember());
        }
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        foreach ($this->row->getMeasures() as $name => $measure) {
            yield $name => new TableValue($measure->getValue());
        }
    }
}
