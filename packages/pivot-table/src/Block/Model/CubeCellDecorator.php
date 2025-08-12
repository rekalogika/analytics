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

namespace Rekalogika\PivotTable\Block\Model;

use Rekalogika\PivotTable\Contracts\Cube\CubeCell;
use Rekalogika\PivotTable\Contracts\Cube\Dimension;
use Rekalogika\PivotTable\Contracts\Cube\MeasureMember;
use Rekalogika\PivotTable\Contracts\Cube\SubtotalDescriptionResolver;

final readonly class CubeCellDecorator implements CubeCell
{
    public function __construct(
        private CubeCell $cubeCell,
        private SubtotalDescriptionResolver $subtotalDescriptionResolver,
        private ?string $subtotalKey = null,
    ) {}

    public function asSubtotal(string $key): self
    {
        return new self(
            cubeCell: $this->cubeCell,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
            subtotalKey: $key,
        );
    }

    public function isSubtotal(): bool
    {
        return $this->subtotalKey !== null;
    }

    #[\Override]
    public function isNull(): bool
    {
        return $this->cubeCell->isNull();
    }

    #[\Override]
    public function getTuple(): array
    {
        if ($this->subtotalKey === null) {
            return $this->cubeCell->getTuple();
        }

        $tuple = [];

        foreach ($this->cubeCell->getTuple() as $key => $value) {
            $tuple[$key] = $value;
        }

        return $tuple;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->cubeCell->getValue();
    }

    private function getDimension(string $name): Dimension
    {
        $tuple = $this->getTuple();

        if (!isset($tuple[$name])) {
            throw new \InvalidArgumentException("Dimension '$name' not found in cube.");
        }

        return $tuple[$name];
    }

    public function getMember(string $dimensionName): mixed
    {
        if ($this->subtotalKey === $dimensionName) {
            return $this->subtotalDescriptionResolver
                ->getSubtotalDescription($this->subtotalKey);
        }

        $dimension = $this->getDimension($dimensionName);
        /** @psalm-suppress MixedAssignment */
        $member = $dimension->getMember();

        if ($member instanceof MeasureMember) {
            return $member->getLegend();
        }

        return $member;
    }

    public function getLegend(string $dimensionName): mixed
    {
        $dimension = $this->getDimension($dimensionName);

        return $dimension->getLegend();
    }

    #[\Override]
    public function slice(string $dimensionName, mixed $member): self
    {
        $result = $this->cubeCell->slice($dimensionName, $member);

        return new self(
            cubeCell: $result,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
        );
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        $cubes = $this->cubeCell->drillDown($dimensionName);

        foreach ($cubes as $cube) {
            yield new self(
                cubeCell: $cube,
                subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
            );
        }
    }

    #[\Override]
    public function rollUp(string $dimensionName): self
    {
        $result = $this->cubeCell->rollUp($dimensionName);

        return new self(
            cubeCell: $result,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
        );
    }

    /**
     * @param list<string> $exceptions
     */
    public function rollUpAllExcept(array $exceptions): self
    {
        $result = $this;
        $allDimensions = array_keys($this->getTuple());
        $dimensionsToRollUp = array_diff($allDimensions, $exceptions);

        foreach ($dimensionsToRollUp as $dimensionName) {
            $result = $result->rollUp($dimensionName);
        }

        return new self(
            cubeCell: $result,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
        );
    }

    /**
     * @return iterable<self>
     */
    public function drillDownWithoutBalancing(string $dimensionName): iterable
    {
        $cubes = $this->cubeCell->drillDown($dimensionName);

        foreach ($cubes as $cube) {
            if ($cube->isNull()) {
                continue;
            }

            yield new self(
                cubeCell: $cube,
                subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
            );
        }
    }

    /**
     * @param list<CubeCell> $prototypeCubeCells
     * @return iterable<self>
     */
    public function drillDownWithPrototypes(
        string $dimensionName,
        array $prototypeCubeCells,
    ): iterable {
        foreach ($prototypeCubeCells as $prototypeCubeCell) {
            $tuple = $prototypeCubeCell->getTuple();
            $dimension = $tuple[$dimensionName]
                ?? throw new \InvalidArgumentException("Dimension '$dimensionName' not found in prototype cube.");

            yield $this->slice(
                dimensionName: $dimensionName,
                member: $dimension->getMember(),
            );
        }
    }
}
