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

namespace Rekalogika\Analytics\Serialization\Implementation;

use Doctrine\Common\Collections\Expr\Expression;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @implements \IteratorAggregate<string,NullDimension>
 */
final readonly class NullTuple implements Tuple, \IteratorAggregate
{
    /**
     * @var class-string
     */
    private string $summaryClass;

    /**
     * @var array<string,NullDimension>
     */
    private array $dimensions;

    /**
     * @param array<string,mixed> $dimensionMembers
     */
    public function __construct(
        SummaryMetadata $summaryMetadata,
        array $dimensionMembers,
        private ?Expression $condition,
    ) {
        $this->summaryClass = $summaryMetadata->getSummaryClass();

        $dimensions = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($dimensionMembers as $name => $member) {
            $dimensions[$name] = new NullDimension(
                name: $name,
                member: $member,
                summaryMetadata: $summaryMetadata,
            );
        }

        $this->dimensions = $dimensions;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->dimensions);
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getDimensionality(): array
    {
        return array_keys($this->dimensions);
    }

    #[\Override]
    public function getByKey(mixed $key): ?NullDimension
    {
        return $this->dimensions[$key] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): mixed
    {
        $keys = array_keys($this->dimensions);

        if (!isset($keys[$index])) {
            return null;
        }

        $name = $keys[$index];

        return $this->dimensions[$name] ?? null;
    }

    #[\Override]
    public function hasKey(mixed $key): bool
    {
        return isset($this->dimensions[$key]);
    }

    #[\Override]
    public function first(): ?NullDimension
    {
        $keys = array_keys($this->dimensions);
        return $keys ? $this->dimensions[$keys[0]] : null;
    }

    #[\Override]
    public function last(): ?NullDimension
    {
        $keys = array_keys($this->dimensions);
        return $keys ? $this->dimensions[end($keys)] : null;
    }

    #[\Override]
    public function getCondition(): ?Expression
    {
        return $this->condition;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->dimensions);
    }
}
