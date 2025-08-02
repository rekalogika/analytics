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

namespace Rekalogika\Analytics\Engine\SummaryQuery\Output;

use Rekalogika\Analytics\Engine\SummaryQuery\Exception\DimensionNamesException;

final readonly class DimensionNames implements \Countable, \Stringable
{
    /**
     * @param list<string> $dimensionNames
     */
    public function __construct(
        private array $dimensionNames,
    ) {}

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->dimensionNames;
    }

    #[\Override]
    public function __toString(): string
    {
        return implode(',', $this->dimensionNames);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dimensionNames);
    }

    public function hasName(string $name): bool
    {
        return \in_array($name, $this->dimensionNames, true);
    }

    public function hasMeasureDimension(): bool
    {
        return $this->hasName('@values');
    }

    public function withoutMeasureDimension(): static
    {
        $dimensionNames = $this->dimensionNames;

        if (($key = array_search('@values', $dimensionNames, true)) !== false) {
            unset($dimensionNames[$key]);
        }

        return new self(array_values($dimensionNames));
    }

    public function first(): ?string
    {
        return $this->dimensionNames[0] ?? null;
    }

    public function last(): ?string
    {
        $dimensionNames = $this->dimensionNames;

        return $dimensionNames !== [] ? $dimensionNames[\count($dimensionNames) - 1] : null;
    }

    public function withoutFirst(): static
    {
        if (empty($this->dimensionNames)) {
            throw new DimensionNamesException('Dimension names cannot be empty.');
        }

        $dimensionNames = $this->dimensionNames;
        array_shift($dimensionNames);

        return new self($dimensionNames);
    }

    public function isEmpty(): bool
    {
        return $this->dimensionNames === [];
    }

    public function removeUpTo(string $name): static
    {
        $dimensionNames = $this->dimensionNames;

        while (
            $dimensionNames !== []
            && $dimensionNames[0] !== $name
        ) {
            array_shift($dimensionNames);
        }

        array_shift($dimensionNames); // remove the name itself

        return new self($dimensionNames);
    }

    /**
     * @param int<1,max>|int<min,-1>|string $name
     */
    public function resolveName(string|int $name): string
    {
        if (\is_string($name)) {
            if (!$this->hasName($name)) {
                throw new DimensionNamesException(\sprintf(
                    'Dimension name "%s" is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->dimensionNames),
                ));
            }

            return $name;
        }

        // if positive, returns the name at the index, start at 1
        if ($name > 0) {
            return $this->dimensionNames[$name - 1]
                ?? throw new DimensionNamesException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->dimensionNames),
                ));
        }

        // if negative, returns the name at the index from the end, start at -1
        if ($name < 0) {
            $index = \count($this->dimensionNames) + $name;

            return $this->dimensionNames[$index]
                ?? throw new DimensionNamesException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->dimensionNames),
                ));
        }

        throw new DimensionNamesException(\sprintf(
            'Invalid dimension name: %s. Must be a string or an integer.',
            var_export($name, true),
        ));
    }
}
