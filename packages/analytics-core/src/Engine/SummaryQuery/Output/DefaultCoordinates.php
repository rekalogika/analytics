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

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Exception\UnexpectedValueException;
use Rekalogika\Analytics\Contracts\Result\Coordinates;
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Contracts\Rekapager\Exception\OutOfBoundsException;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final class DefaultCoordinates implements Coordinates, \IteratorAggregate
{
    private ?string $signature = null;

    /**
     * @var list<string>|null
     */
    private ?array $dimensionality = null;

    /**
     * @var array<string,DefaultDimension>
     */
    private readonly array $dimensions;

    /**
     * @param class-string $summaryClass
     * @param iterable<DefaultDimension> $dimensions
     */
    public function __construct(
        private readonly string $summaryClass,
        iterable $dimensions,
        private readonly ?Expression $condition,
    ) {
        $dimensionsArray = [];

        foreach ($dimensions as $dimension) {
            $dimensionsArray[$dimension->getName()] = $dimension;
        }

        $this->dimensions = $dimensionsArray;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $key = array_keys($this->dimensions)[$index] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->dimensions[$key];
    }

    #[\Override]
    public function first(): mixed
    {
        $firstKey = array_key_first($this->dimensions);

        if ($firstKey === null) {
            return null;
        }

        return $this->dimensions[$firstKey];
    }

    #[\Override]
    public function last(): mixed
    {
        $lastKey = array_key_last($this->dimensions);

        if ($lastKey === null) {
            return null;
        }

        return $this->dimensions[$lastKey];
    }


    public function append(DefaultDimension $dimension): static
    {
        return new self(
            summaryClass: $this->summaryClass,
            dimensions: [...$this->dimensions, $dimension],
            condition: $this->condition,
        );
    }

    /**
     * @param non-empty-list<string> $dimensionNames
     */
    public function withoutDimensions(array $dimensionNames): static
    {
        $dimensionsWithout = $this->dimensions;

        foreach ($dimensionNames as $name) {
            if (!isset($this->dimensions[$name])) {
                throw new UnexpectedValueException(\sprintf(
                    'Dimension "%s" not found in coordinates',
                    $name,
                ));
            }

            unset($dimensionsWithout[$name]);
        }

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensionsWithout,
            condition: $this->condition,
        );
    }

    /**
     * @param list<string> $dimensionNames
     */
    public function withDimensions(array $dimensionNames): static
    {
        $dimensionsOnly = [];

        foreach ($dimensionNames as $name) {
            if (!isset($this->dimensions[$name])) {
                throw new OutOfBoundsException(\sprintf(
                    'Dimension "%s" not found in coordinates',
                    $name,
                ));
            }

            $dimensionsOnly[$name] = $this->dimensions[$name];
        }

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensionsOnly,
            condition: $this->condition,
        );
    }

    public function getMeasureName(): ?string
    {
        $measureDimension = $this->dimensions['@values'] ?? null;

        if ($measureDimension === null) {
            return null;
        }

        /** @psalm-suppress MixedAssignment */
        $member = $measureDimension->getMember();

        if (!$member instanceof MeasureMember) {
            throw new UnexpectedValueException(\sprintf(
                'Expected MeasureMember, got %s',
                get_debug_type($member),
            ));
        }

        return $member->getMeasureProperty();
    }

    #[\Override]
    public function get(mixed $key): ?DefaultDimension
    {
        return $this->dimensions[$key] ?? null;
    }

    #[\Override]
    public function has(mixed $key): bool
    {
        return isset($this->dimensions[$key]);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->dimensions;
    }

    #[\Override]
    public function getPredicate(): ?Expression
    {
        return $this->condition;
    }

    /**
     * Note: the signature does not account for the dimension ordering.
     * Different ordering will produce the same signature as long as the
     * dimensions and their members are the same.
     *
     * @return string
     */
    public function getSignature(): string
    {
        if ($this->signature !== null) {
            return $this->signature;
        }

        $dimensions = $this->dimensions;
        ksort($dimensions);

        $signatures = array_map(
            static fn(DefaultDimension $dimension): string => $dimension->getSignature(),
            $dimensions,
        );

        return $this->signature = hash(
            'xxh128',
            implode('|', $signatures),
        );
    }

    /**
     * @return list<string>
     */
    #[\Override]
    public function getDimensionality(): array
    {
        if ($this->dimensionality !== null) {
            return $this->dimensionality;
        }

        $dimensionality = array_keys($this->dimensions);
        sort($dimensionality);

        return $this->dimensionality = $dimensionality;
    }

    /**
     * Get the list of dimension names in the order they were added.
     *
     * @return list<string>
     */
    public function getDimensionNames(): array
    {
        return array_keys($this->dimensions);
    }
}
