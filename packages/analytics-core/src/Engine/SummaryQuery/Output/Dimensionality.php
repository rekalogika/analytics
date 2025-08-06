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

final readonly class Dimensionality
{
    /**
     * @param list<string> $dimensionNames
     */
    public static function create(array $dimensionNames): self
    {
        return new self(
            ancestors: [],
            current: null,
            descendants: $dimensionNames,
        );
    }


    /**
     * @param list<string> $descendants
     * @param string|null $current Null means root
     * @param list<string> $ancestors
     */
    private function __construct(
        private array $ancestors,
        private ?string $current,
        private array $descendants,
    ) {}

    /**
     * @return list<string>
     */
    public function getAncestors(): array
    {
        return $this->ancestors;
    }

    public function getCurrent(): ?string
    {
        return $this->current;
    }

    /**
     * @return list<string>
     */
    public function getDescendants(): array
    {
        return $this->descendants;
    }

    public function getSignature(): string
    {
        return hash('xxh128', serialize($this));
    }

    public function descend(string $name): self
    {
        $position = array_search($name, $this->descendants, true);

        if ($position === false) {
            throw new DimensionNamesException(\sprintf(
                'Dimension name "%s" is not found in the dimension names: %s',
                $name,
                implode(', ', $this->descendants),
            ));
        }

        // convert to 1-based index
        $position += 1;

        if ($position > \count($this->descendants)) {
            throw new DimensionNamesException(\sprintf(
                'Levels %d is greater than the number of descendants %d.',
                $position,
                \count($this->descendants),
            ));
        }

        // get ancestors & descendants
        $ancestors = $this->ancestors;
        $descendants = $this->descendants;
        $current = $this->current;

        // move current to end of ancestors
        if ($current !== null) {
            $ancestors[] = $current;
        }

        // get the first n $levels of descendants
        $descended = \array_slice($descendants, 0, $position);

        // remove the first n $levels of descendants
        $descendants = \array_slice($descendants, $position);

        // current is the last of the descended
        $current = $descended !== [] ? array_pop($descended) : null;

        // current cannot be null
        if ($current === null && $descendants !== []) {
            throw new DimensionNamesException('Current cannot be null when there are descendants.');
        }

        return new self(
            ancestors: $ancestors,
            current: $current,
            descendants: $descendants,
        );
    }

    // /**
    //  * @return list<string>
    //  */
    // public function toArray(): array
    // {
    //     return $this->descendants;
    // }

    // public function getSignature(): string
    // {
    //     return implode(',', $this->descendants);
    // }

    // #[\Override]
    // public function __toString(): string
    // {
    //     return implode(',', $this->descendants);
    // }

    // #[\Override]
    // public function count(): int
    // {
    //     return \count($this->descendants);
    // }

    public function hasDescendant(string $name): bool
    {
        return \in_array($name, $this->descendants, true);
    }

    // public function hasMeasureDimension(): bool
    // {
    //     return $this->hasName('@values');
    // }

    // public function withoutMeasureDimension(): static
    // {
    //     $dimensionNames = $this->descendants;

    //     if (($key = array_search('@values', $dimensionNames, true)) !== false) {
    //         unset($dimensionNames[$key]);
    //     }

    //     return new self(array_values($dimensionNames));
    // }

    // public function first(): ?string
    // {
    //     return $this->descendants[0] ?? null;
    // }

    // public function last(): ?string
    // {
    //     $dimensionNames = $this->descendants;

    //     return $dimensionNames !== [] ? $dimensionNames[\count($dimensionNames) - 1] : null;
    // }

    // public function withoutFirst(): static
    // {
    //     if (empty($this->descendants)) {
    //         throw new DimensionNamesException('Dimension names cannot be empty.');
    //     }

    //     $dimensionNames = $this->descendants;
    //     array_shift($dimensionNames);

    //     return new self($dimensionNames);
    // }

    // public function isEmpty(): bool
    // {
    //     return $this->descendants === [];
    // }

    // public function

    // public function removeUpTo(string $name): static
    // {
    //     $dimensionNames = $this->descendants;

    //     while (
    //         $dimensionNames !== []
    //         && $dimensionNames[0] !== $name
    //     ) {
    //         array_shift($dimensionNames);
    //     }

    //     array_shift($dimensionNames); // remove the name itself

    //     return new self($dimensionNames);
    // }

    /**
     * @param int<1,max>|int<min,-1>|string $name
     */
    public function resolveName(string|int $name): string
    {
        if (\is_string($name)) {
            if (!$this->hasDescendant($name)) {
                throw new DimensionNamesException(\sprintf(
                    'Dimension name "%s" is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->descendants),
                ));
            }

            return $name;
        }

        // if positive, returns the name at the index, start at 1
        if ($name > 0) {
            return $this->descendants[$name - 1]
                ?? throw new DimensionNamesException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->descendants),
                ));
        }

        // if negative, returns the name at the index from the end, start at -1
        if ($name < 0) {
            $index = \count($this->descendants) + $name;

            return $this->descendants[$index]
                ?? throw new DimensionNamesException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->descendants),
                ));
        }

        throw new DimensionNamesException(\sprintf(
            'Invalid dimension name: %s. Must be a string or an integer.',
            var_export($name, true),
        ));
    }
}
