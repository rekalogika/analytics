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

namespace Rekalogika\Analytics\PivotTable\Util;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\PivotTable\Model\Table\DimensionLabel;
use Rekalogika\Analytics\PivotTable\Model\Table\DimensionMember;
use Rekalogika\Analytics\PivotTable\Model\Table\MeasureLabel;
use Rekalogika\Analytics\PivotTable\Model\Table\MeasureValue;

/**
 * Identity map for objects that represent properties of a pivot table.
 */
final class TablePropertyMap
{
    /**
     * @var \WeakMap<Dimension,DimensionLabel>
     */
    private \WeakMap $dimensionToLabel;

    /**
     * @var \WeakMap<Dimension,DimensionMember>
     */
    private \WeakMap $dimensionToMember;

    /**
     * @var \WeakMap<Row,MeasureValue>
     */
    private \WeakMap $rowToMeasureValue;

    /**
     * @var \WeakMap<Row,MeasureLabel>
     */
    private \WeakMap $rowToMeasureLabel;

    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->dimensionToLabel = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->dimensionToMember = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->rowToMeasureValue = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->rowToMeasureLabel = new \WeakMap();
    }

    public function getDimensionLabel(Dimension $dimension): DimensionLabel
    {
        if (!$this->dimensionToLabel->offsetExists($dimension)) {
            $this->dimensionToLabel->offsetSet($dimension, new DimensionLabel($dimension));
        }

        return $this->dimensionToLabel->offsetGet($dimension) ?? throw new LogicException('Dimension label not found in the map.');
    }

    public function getDimensionMember(Dimension $dimension): DimensionMember
    {
        if (!$this->dimensionToMember->offsetExists($dimension)) {
            $this->dimensionToMember->offsetSet($dimension, new DimensionMember($dimension));
        }

        return $this->dimensionToMember->offsetGet($dimension) ?? throw new LogicException('Dimension member not found in the map.');
    }

    public function getMeasureValue(Row $row): MeasureValue
    {
        if (!$this->rowToMeasureValue->offsetExists($row)) {
            $this->rowToMeasureValue->offsetSet($row, new MeasureValue($row));
        }

        return $this->rowToMeasureValue->offsetGet($row) ?? throw new LogicException('Measure value not found in the map.');
    }

    public function getMeasureLabel(Row $row): MeasureLabel
    {
        if (!$this->rowToMeasureLabel->offsetExists($row)) {
            $this->rowToMeasureLabel->offsetSet($row, new MeasureLabel($row));
        }

        return $this->rowToMeasureLabel->offsetGet($row) ?? throw new LogicException('Measure label not found in the map.');
    }
}
