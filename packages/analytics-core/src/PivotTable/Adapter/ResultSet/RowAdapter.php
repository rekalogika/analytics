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

namespace Rekalogika\Analytics\PivotTable\Adapter\ResultSet;

use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\PivotTable\Contracts\Result\ResultRow;
use Rekalogika\PivotTable\Contracts\Result\Tuple;
use Rekalogika\PivotTable\Contracts\Result\Values;

final readonly class RowAdapter implements ResultRow
{
    public function __construct(
        private Row $row,
    ) {}

    #[\Override]
    public function getTuple(): Tuple
    {
        return new TupleAdapter($this->row->getTuple());
    }

    #[\Override]
    public function getValues(): Values
    {
        return new ValuesAdapter($this->row->getMeasures());
    }
}
