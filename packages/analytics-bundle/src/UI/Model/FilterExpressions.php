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

namespace Rekalogika\Analytics\Bundle\UI\Model;

use Rekalogika\Analytics\Bundle\Formatter\Stringifier;
use Rekalogika\Analytics\SummaryManager\SummaryQuery;

/**
 * @implements \IteratorAggregate<string,FilterExpression>
 * @implements \ArrayAccess<string,FilterExpression>
 */
final class FilterExpressions implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @param class-string $summaryClass
     * @param list<string> $dimensions
     * @param array<string,mixed> $arrayExpressions
     */
    public function __construct(
        private string $summaryClass,
        array $dimensions,
        private array $arrayExpressions,
        private SummaryQuery $query,
        private Stringifier $stringifier,
    ) {
        $this->setFilters($dimensions);
    }

    /**
     * @var array<string,EqualFilter>
     */
    private array $expressions = [];

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->expressions[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->expressions[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Use setFilters() to set filters');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Use setFilters() to set filters');
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->expressions);
    }

    /**
     * @param list<string> $filters
     */
    private function setFilters(array $filters): void
    {
        foreach ($filters as $filter) {
            $filterArray = $this->arrayExpressions[$filter] ?? [];

            if (!\is_array($filterArray)) {
                throw new \InvalidArgumentException('Invalid filter array');
            }

            /** @var array<string,mixed> $filterArray */
            $this->expressions[$filter] = $this->createEqualFilter($filter, $filterArray);
        }
    }

    /**
     * @param array<string,mixed> $input
     */
    private function createEqualFilter(
        string $dimension,
        array $input,
    ): EqualFilter {
        return new EqualFilter(
            query: $this->query,
            stringifier: $this->stringifier,
            dimension: $dimension,
            inputArray: $input,
        );
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    public function applyToQuery(): void
    {
        foreach ($this->expressions as $expression) {
            $expression->applyToQuery($this->query);
        }
    }
}
