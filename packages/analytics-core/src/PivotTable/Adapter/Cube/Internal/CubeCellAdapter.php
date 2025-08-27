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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube\Internal;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\PivotTable\Model\Cube\DimensionMember;
use Rekalogika\Analytics\PivotTable\Util\PropertyMap;
use Rekalogika\PivotTable\Contracts\Cube\CubeCell as PivotTableCubeCell;

final readonly class CubeCellAdapter implements PivotTableCubeCell
{
    public function __construct(
        private CubeCell $cubeCell,
        private PropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function getCoordinates(): array
    {
        $coordinates = [];

        foreach ($this->cubeCell->getCoordinates() as $key => $dimension) {
            $coordinates[$key] = $this->propertyMap->getDimension($dimension);
        }

        return $coordinates;
    }

    private function getMeasureName(): ?string
    {
        $measureMember = $this->cubeCell
            ->getCoordinates()
            ->get('@values')
            ?->getRawMember();

        if ($measureMember === \null) {
            return null;
        }

        if (!$measureMember instanceof MeasureMember) {
            throw new LogicException(
                'Expected a MeasureMember for the "@values" dimension, but got: ' . get_debug_type($measureMember),
            );
        }

        return $measureMember->getMeasureProperty();
    }

    #[\Override]
    public function getValue(): mixed
    {
        $measureName = $this->getMeasureName();

        if ($measureName === null) {
            return null;
        }

        return $this->propertyMap
            ->getMeasureValue($this->cubeCell)
            ->withMeasureName($measureName);
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->cubeCell->isNull();
    }

    #[\Override]
    public function slice(string $dimensionName, mixed $member): self
    {
        if ($member instanceof DimensionMember) {
            /** @psalm-suppress MixedAssignment */
            $member = $member->getDimension()->getRawMember();
        } elseif ($member instanceof MeasureMemberAdapter) {
            $member = $member->getWrapped();
        } else {
            throw new LogicException(
                'Expected a DimensionMember for slicing, but got: ' . get_debug_type($member),
            );
        }

        $result = $this->cubeCell->slice($dimensionName, $member);

        if ($result === null) {
            throw new LogicException('Slice operation returned null. The member might not exist.');
        }

        return new self(
            cubeCell: $result,
            propertyMap: $this->propertyMap,
        );
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        $result = $this->cubeCell->drillDown($dimensionName);

        foreach ($result as $cube) {
            yield new self(
                cubeCell: $cube,
                propertyMap: $this->propertyMap,
            );
        }
    }

    #[\Override]
    public function rollUp(string $dimensionName): self
    {
        $result = $this->cubeCell->rollUp($dimensionName);

        return new self(
            cubeCell: $result,
            propertyMap: $this->propertyMap,
        );
    }
}
