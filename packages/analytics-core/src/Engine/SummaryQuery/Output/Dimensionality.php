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

use Rekalogika\Analytics\Engine\SummaryQuery\Exception\DimensionalityException;

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

    /**
     * @return list<string>
     */
    public function getAncestorsToCurrent(): array
    {
        if ($this->current === null) {
            return [];
        }

        $ancestors = $this->ancestors;
        $ancestors[] = $this->current;

        return $ancestors;
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
            throw new DimensionalityException(\sprintf(
                'Dimension name "%s" is not found in the dimension names: %s',
                $name,
                implode(', ', $this->descendants),
            ));
        }

        // convert to 1-based index
        $position += 1;

        if ($position > \count($this->descendants)) {
            throw new DimensionalityException(\sprintf(
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
            throw new DimensionalityException('Current cannot be null when there are descendants.');
        }

        return new self(
            ancestors: $ancestors,
            current: $current,
            descendants: $descendants,
        );
    }

    public function hasDescendant(string $name): bool
    {
        return \in_array($name, $this->descendants, true);
    }

    /**
     * @param int<1,max>|int<min,-1>|string $name
     */
    public function resolveName(string|int $name): string
    {
        if (\is_string($name)) {
            if (!$this->hasDescendant($name)) {
                throw new DimensionalityException(\sprintf(
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
                ?? throw new DimensionalityException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->descendants),
                ));
        }

        // if negative, returns the name at the index from the end, start at -1
        if ($name < 0) {
            $index = \count($this->descendants) + $name;

            return $this->descendants[$index]
                ?? throw new DimensionalityException(\sprintf(
                    'Dimension name at index %d is not found in the dimension names: %s',
                    $name,
                    implode(', ', $this->descendants),
                ));
        }

        throw new DimensionalityException(\sprintf(
            'Invalid dimension name: %s. Must be a string or an integer.',
            var_export($name, true),
        ));
    }
}
