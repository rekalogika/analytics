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

use Rekalogika\PivotTable\Contracts\Cube\Cube;
use Rekalogika\PivotTable\Contracts\Cube\Dimension;
use Rekalogika\PivotTable\Contracts\Cube\MeasureMember;
use Rekalogika\PivotTable\Contracts\Cube\SubtotalDescriptionResolver;

final readonly class CubeDecorator implements Cube
{
    public function __construct(
        private Cube $cube,
        private SubtotalDescriptionResolver $subtotalDescriptionResolver,
        private ?string $subtotalKey = null,
    ) {}

    public function asSubtotal(string $key): self
    {
        return new self(
            cube: $this->cube,
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
        return $this->cube->isNull();
    }

    #[\Override]
    public function getTuple(): array
    {
        if ($this->subtotalKey === null) {
            return $this->cube->getTuple();
        }

        $tuple = [];

        foreach ($this->cube->getTuple() as $key => $value) {
            $tuple[$key] = $value;
        }

        return $tuple;
    }

    #[\Override]
    public function getValue(): mixed
    {
        return $this->cube->getValue();
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
        $result = $this->cube->slice($dimensionName, $member);

        return new self(
            cube: $result,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
        );
    }

    #[\Override]
    public function drillDown(string $dimensionName): iterable
    {
        $cubes = $this->cube->drillDown($dimensionName);

        foreach ($cubes as $cube) {
            yield new self(
                cube: $cube,
                subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
            );
        }
    }

    #[\Override]
    public function rollUp(string $dimensionName): self
    {
        $result = $this->cube->rollUp($dimensionName);

        return new self(
            cube: $result,
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
            cube: $result,
            subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
        );
    }

    /**
     * @return iterable<self>
     */
    public function drillDownWithoutBalancing(string $dimensionName): iterable
    {
        $cubes = $this->cube->drillDown($dimensionName);

        foreach ($cubes as $cube) {
            if ($cube->isNull()) {
                continue;
            }

            yield new self(
                cube: $cube,
                subtotalDescriptionResolver: $this->subtotalDescriptionResolver,
            );
        }
    }

    /**
     * @param list<Cube> $prototypeCubes
     * @return iterable<self>
     */
    public function drillDownWithPrototypes(
        string $dimensionName,
        array $prototypeCubes,
    ): iterable {
        foreach ($prototypeCubes as $prototypeCube) {
            $tuple = $prototypeCube->getTuple();
            $dimension = $tuple[$dimensionName]
                ?? throw new \InvalidArgumentException("Dimension '$dimensionName' not found in prototype cube.");

            yield $this->slice(
                dimensionName: $dimensionName,
                member: $dimension->getMember(),
            );
        }
    }
}
