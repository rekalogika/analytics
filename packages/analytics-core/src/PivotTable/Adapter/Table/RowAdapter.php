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
use Rekalogika\Analytics\PivotTable\Util\TablePropertyMap;
use Rekalogika\PivotTable\Contracts\Row as PivotTableRow;

final readonly class RowAdapter implements PivotTableRow
{
    public function __construct(
        private Row $row,
        private TablePropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        foreach ($this->row->getTuple() as $key => $dimension) {
            yield $key => $this->propertyMap->getDimensionMember($dimension);
        }
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        foreach ($this->row->getMeasures() as $key => $_) {
            yield $key =>
                $this->propertyMap
                ->getMeasureValue($this->row)
                ->withMeasureName($key);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function getLegends(): array
    {
        $legends = [];

        foreach ($this->row->getTuple() as $key => $dimension) {
            $legends[$key] = $this->propertyMap->getDimensionLabel($dimension);
        }

        foreach ($this->row->getMeasures() as $key => $_) {
            $legends[$key] =
                $this->propertyMap
                ->getMeasureLabel($this->row)
                ->withMeasureName($key);
        }

        return $legends;
    }
}
