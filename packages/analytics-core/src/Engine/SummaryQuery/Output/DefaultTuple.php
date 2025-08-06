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

        ksort($dimensionsArray);

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

    public function without(string $dimensionName): static
    {
        if (!isset($this->dimensions[$dimensionName])) {
            throw new UnexpectedValueException(\sprintf(
                'Dimension "%s" not found in tuple',
                $dimensionName,
            ));
        }

        $dimensionsWithout = $this->dimensions;
        unset($dimensionsWithout[$dimensionName]);

        return new self(
            summaryClass: $this->summaryClass,
            dimensions: $dimensionsWithout,
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
    public function getCondition(): ?Expression
    {
        return $this->condition;
    }

    public function getSignature(): string
    {
        if ($this->signature !== null) {
            return $this->signature;
        }

        $dimensions = $this->dimensions;

        $signatures = array_map(
            static fn(DefaultDimension $dimension): string => $dimension->getSignature(),
            $dimensions,
        );

        return $this->signature = hash('xxh128', serialize($signatures));
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

        return $this->dimensionality = $dimensionality;
    }
}
