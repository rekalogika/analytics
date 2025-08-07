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

namespace Rekalogika\PivotTable\TableFramework;

final readonly class Cube
{
    /**
     * @var array<string,mixed>
     * */
    private array $tuple;

    private mixed $value;
    private bool $isNull;

    /**
     * @param array<string,mixed> $tuple
     */
    public function __construct(
        private CubeManager $manager,
        array $tuple,
        private bool $isSubtotal = false,
    ) {
        if (\array_key_exists('@values', $tuple) && !\is_string($tuple['@values'])) {
            throw new \InvalidArgumentException(
                "Tuple must contain '@values' dimension with a string value.",
            );
        }

        $this->value = $this->manager->getValue($tuple);
        $this->isNull = $this->manager->isNull($tuple);
        $this->tuple = $tuple;
    }

    public function asSubtotal(): self
    {
        return new self(
            manager: $this->manager,
            tuple: $this->tuple,
            isSubtotal: true,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getTuple(): array
    {
        return $this->tuple;
    }

    public function getMember(string $dimensionName): mixed
    {
        if (!\array_key_exists($dimensionName, $this->tuple)) {
            throw new \InvalidArgumentException(
                "Dimension '$dimensionName' does not exist in the tuple.",
            );
        }

        if ($dimensionName === '@values') {
            $measureName = $this->tuple['@values'] ?? null;

            if (!\is_string($measureName)) {
                throw new \InvalidArgumentException(
                    "Measure name for '@values' dimension is not set.",
                );
            }

            return $this->manager->getLegend($measureName);
        }

        return $this->tuple[$dimensionName];
    }

    public function getLegend(string $dimensionName): mixed
    {
        if (!\array_key_exists($dimensionName, $this->tuple)) {
            throw new \InvalidArgumentException(
                "Dimension '$dimensionName' does not exist in the tuple.",
            );
        }

        return $this->manager->getLegend($dimensionName);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }

    public function isSubtotal(): bool
    {
        return $this->isSubtotal;
    }

    //
    // operations
    //

    /**
     * @return list<self>
     */
    public function drillDown(string $dimensionName): array
    {
        return $this->manager->drillDown($this->tuple, $dimensionName);
    }

    public function slice(string $dimensionName, mixed $member): self
    {
        return $this->manager->slice($this->tuple, $dimensionName, $member);
    }

    /**
     * @param list<string> $keys
     */
    public function rollUp(array $keys): self
    {
        return $this->manager->rollUp($this->tuple, $keys);
    }

    /**
     * @param string $dimensionName
     * @param iterable<self> $cubes
     * @return list<self>
     */
    public function multipleSlicesFromCubes(
        string $dimensionName,
        iterable $cubes,
    ): array {
        return $this->manager->multipleSlicesFromCubes(
            tuple: $this->tuple,
            dimensionName: $dimensionName,
            cubes: $cubes,
        );
    }
}
