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

namespace Rekalogika\Analytics\PivotTable\Adapter\Cube;

use Rekalogika\Analytics\Contracts\Exception\LogicException;
use Rekalogika\Analytics\Contracts\Result\CubeCell;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\PivotTable\Model\Table\DimensionMember;
use Rekalogika\Analytics\PivotTable\Util\TablePropertyMap;
use Rekalogika\PivotTable\Contracts\Cube\Cube;

final readonly class CubeAdapter implements Cube
{
    public static function adapt(CubeCell $cell): self
    {
        return new self(
            cell: $cell,
            propertyMap: new TablePropertyMap(),
        );
    }

    private function __construct(
        private CubeCell $cell,
        private TablePropertyMap $propertyMap,
    ) {}

    #[\Override]
    public function getTuple(): array
    {
        $tuple = [];

        foreach ($this->cell->getTuple() as $key => $dimension) {
            $tuple[$key] = $this->propertyMap->getDimension($dimension);
        }

        return $tuple;
    }

    private function getMeasureName(): ?string
    {
        $measureMember = $this->cell
            ->getTuple()
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
            ->getMeasureValue($this->cell)
            ->withMeasureName($measureName);
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->cell->isNull();
    }

    #[\Override]
    public function slice(string $dimensionName, mixed $member): Cube
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

        $result = $this->cell->slice($dimensionName, $member);

        return new self(
            cell: $result,
            propertyMap: $this->propertyMap,
        );
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        $result = $this->cell->drillDown($dimensionName);

        foreach ($result as $cube) {
            yield new self(
                cell: $cube,
                propertyMap: $this->propertyMap,
            );
        }
    }

    #[\Override]
    public function rollUp(string $dimensionName): Cube
    {
        $result = $this->cell->rollUp($dimensionName);

        return new self(
            cell: $result,
            propertyMap: $this->propertyMap,
        );
    }
}
