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

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\PivotTable\Util\TablePropertyMap;
use Rekalogika\PivotTable\Contracts\Table\Row as PivotTableRow;

/**
 * @deprecated
 */
final readonly class RowAdapter implements PivotTableRow
{
    public function __construct(
        private CubeCell $cell,
        private TablePropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function getDimensions(): iterable
    {
        foreach ($this->cell->getTuple() as $key => $dimension) {
            if ($key === '@values') {
                $member = $dimension->getMember();

                if (!$member instanceof MeasureMember) {
                    throw new LogicException(
                        'Expected a MeasureMember for the "@values" dimension, but got: ' . get_debug_type($member),
                    );
                }

                yield $key => $member->getMeasureProperty();
            } else {
                yield $key => $this->propertyMap->getDimensionMember($dimension);
            }
        }
    }

    #[\Override]
    public function getMeasures(): iterable
    {
        foreach ($this->cell->getMeasures() as $key => $_) {
            yield $key =>
                $this->propertyMap
                ->getMeasureValue($this->cell)
                ->withMeasureName($key);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function getLegends(): array
    {
        $legends = [];

        foreach ($this->cell->getTuple() as $key => $dimension) {
            $legends[$key] = $this->propertyMap->getDimensionLabel($dimension);
        }

        foreach ($this->cell->getMeasures() as $key => $_) {
            $legends[$key] =
                $this->propertyMap
                ->getMeasureLabel($this->cell)
                ->withMeasureName($key);
        }

        return $legends;
    }
}
