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
use Rekalogika\Analytics\Common\Exception\BadMethodCallException;
use Rekalogika\Analytics\Contracts\Result\Dimension;
use Rekalogika\Analytics\Contracts\Result\Measures;
use Rekalogika\Analytics\Contracts\Result\Row;
use Rekalogika\Analytics\Contracts\Result\Tuple;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;

/**
 * @implements \IteratorAggregate<string,NullDimension>
 */
final readonly class NullRow implements Row, \IteratorAggregate
{
    /**
     * @var class-string
     */
    private string $summaryClass;
    private NullMeasures $measures;

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
        $this->measures = new NullMeasures($summaryMetadata);

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
    public function getMeasures(): Measures
    {
        return $this->measures;
    }

    #[\Override]
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    #[\Override]
    public function getByName(string $name): ?Dimension
    {
        return $this->dimensions[$name] ?? null;
    }

    #[\Override]
    public function getByIndex(int $index): ?Dimension
    {
        $keys = array_keys($this->dimensions);

        if (!isset($keys[$index])) {
            return null;
        }

        return $this->dimensions[$keys[$index]];
    }

    #[\Override]
    public function has(string $name): bool
    {
        return isset($this->dimensions[$name]);
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

    /**
     * @todo implement
     */
    #[\Override]
    public function isSame(Tuple $other): bool
    {
        throw new BadMethodCallException('Not implemented yet');
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
