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
use Rekalogika\Analytics\Contracts\Result\MeasureMember;
use Rekalogika\Analytics\Contracts\Result\Tuple;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final class DefaultTuple implements Tuple, \IteratorAggregate
{
    private ?string $signature = null;

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

    public function append(DefaultDimension $dimension): static
    {
        return new self(
            summaryClass: $this->summaryClass,
            dimensions: [...$this->dimensions, $dimension],
            condition: $this->condition,
        );
    }

    public function withoutMeasure(): static
    {
        $dimensionsWithoutMeasure = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension->getName() === '@values') {
                continue;
            }

            $dimensionsWithoutMeasure[] = $dimension;
        }

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensionsWithoutMeasure,
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

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->dimensions);
    }

    #[\Override]
    public function getByIndex(int $index): ?DefaultDimension
    {
        $names = array_keys($this->dimensions);

        if (!isset($names[$index])) {
            return null;
        }

        return $this->dimensions[$names[$index]];
    }

    #[\Override]
    public function getByKey(mixed $key): ?DefaultDimension
    {
        return $this->dimensions[$key] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        return isset($this->dimensions[$key]);
    }

    #[\Override]
    public function first(): ?DefaultDimension
    {
        $keys = array_keys($this->dimensions);
        return $keys ? $this->dimensions[$keys[0]] : null;
    }

    #[\Override]
    public function last(): ?DefaultDimension
    {
        $keys = array_keys($this->dimensions);
        return $keys ? $this->dimensions[end($keys)] : null;
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

    /**
     * @return array<string,mixed>
     */
    public function getMembers(): array
    {
        $members = [];

        foreach ($this->dimensions as $dimension) {
            /** @psalm-suppress MixedAssignment */
            $members[$dimension->getName()] = $dimension->getMember();
        }

        return $members;
    }

    #[\Override]
    public function getCondition(): ?Expression
    {
        return $this->condition;
    }

    public function getSignature(): string
    {
        if ($this->signature !== null) {
            return $this->signature;
        }

        $signatures = array_map(
            static fn(DefaultDimension $dimension): string => $dimension->getSignature(),
            $this->dimensions,
        );

        return $this->signature = hash('xxh128', serialize($signatures));
    }

    public function getWithoutValues(): self
    {
        $dimensionsWithoutValues = [];

        foreach ($this->dimensions as $dimension) {
            if ($dimension->getName() === '@values') {
                continue;
            }

            $dimensionsWithoutValues[] = $dimension;
        }

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensionsWithoutValues,
            condition: $this->condition,
        );
    }

    /**
     * @param int<0,max> $n
     */
    public function withFirstNDimensions(int $n): self
    {
        $dimensions = \array_slice($this->dimensions, 0, $n);

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensions,
            condition: $this->condition,
        );
    }

    /**
     * @param int<0,max> $n
     */
    public function withUntilLastNthDimension(int $n): self
    {
        $dimensions = \array_slice($this->dimensions, 0, -$n);

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensions,
            condition: $this->condition,
        );
    }
}
