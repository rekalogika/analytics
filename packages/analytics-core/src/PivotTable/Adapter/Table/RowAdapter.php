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
    /**
     * @param list<string> $measures The measures that will be displayed in the
     * table
     */
    public function __construct(
        private Row $row,
        private array $measures,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        foreach ($this->row->getCoordinates() as $name => $dimension) {
            yield $name => new TableMember($dimension->getMember());
        }
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        foreach ($this->measures as $measureName) {
            /** @psalm-suppress MixedAssignment */
            $value = $this->row->getMeasures()->get($measureName)?->getValue();
            yield $measureName => new TableValue($value);
        }
    }
}
