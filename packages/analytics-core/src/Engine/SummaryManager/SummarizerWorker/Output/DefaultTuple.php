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

namespace Rekalogika\Analytics\Engine\SummaryManager\SummarizerWorker\Output;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Result\Tuple;

/**
 * @implements \IteratorAggregate<string,DefaultDimension>
 */
final readonly class DefaultTuple implements Tuple, \IteratorAggregate
{
    /**
     * @var array<string,DefaultDimension>
     */
    private array $dimensions;

    /**
     * @param class-string $summaryClass
     * @param iterable<DefaultDimension> $dimensions
     */
    public function __construct(
        private string $summaryClass,
        iterable $dimensions,
        private ?Expression $condition,
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

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->dimensions);
    }

    #[\Override]
    public function getByName(string $name): ?DefaultDimension
    {
        return $this->dimensions[$name] ?? null;
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
    public function has(string $name): bool
    {
        return isset($this->dimensions[$name]);
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
    public function isSame(Tuple $other): bool
    {
        if ($this->count() !== $other->count()) {
            return false;
        }

        foreach ($this->dimensions as $name => $dimension) {
            if (!$other->has($name)) {
                return false;
            }

            if (!$dimension->isSame($other->getByName($name))) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
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
        $signatures = array_map(
            static fn(DefaultDimension $dimension): string => $dimension->getSignature(),
            $this->dimensions,
        );

        return hash('xxh128', serialize($signatures));
    }

    public function getNamesSignature(): string
    {
        $names = array_keys($this->dimensions);

        return hash('xxh128', serialize($names));
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
