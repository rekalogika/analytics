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
use Rekalogika\Analytics\Contracts\Result\Cell;
use Rekalogika\Analytics\Contracts\Result\Dimension;
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
     * @var \WeakMap<Cell,MeasureValue>
     */
    private \WeakMap $cellToMeasureValue;

    /**
     * @var \WeakMap<Cell,MeasureLabel>
     */
    private \WeakMap $cellToMeasureLabel;

    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->dimensionToLabel = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->dimensionToMember = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->cellToMeasureValue = new \WeakMap();
        /** @psalm-suppress PropertyTypeCoercion */
        $this->cellToMeasureLabel = new \WeakMap();
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

    public function getMeasureValue(Cell $cell): MeasureValue
    {
        if (!$this->cellToMeasureValue->offsetExists($cell)) {
            $this->cellToMeasureValue->offsetSet($cell, new MeasureValue($cell));
        }

        return $this->cellToMeasureValue->offsetGet($cell) ?? throw new LogicException('Measure value not found in the map.');
    }

    public function getMeasureLabel(Cell $cell): MeasureLabel
    {
        if (!$this->cellToMeasureLabel->offsetExists($cell)) {
            $this->cellToMeasureLabel->offsetSet($cell, new MeasureLabel($cell));
        }

        return $this->cellToMeasureLabel->offsetGet($cell) ?? throw new LogicException('Measure label not found in the map.');
    }
}
