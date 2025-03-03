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

use Rekalogika\Analytics\SummaryManager\SummaryQuery;

/**
 * @implements \IteratorAggregate<string,EqualFilter>
 * @implements \ArrayAccess<string,EqualFilter>
 */
final class FilterExpressions implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @param class-string $summaryClass
     * @param list<string> $dimensions
     * @param array<string,array<array-key,string>> $arrayExpressions
     */
    public function __construct(
        private string $summaryClass,
        array $dimensions,
        private array $arrayExpressions,
        private SummaryQuery $query,
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
            $values = $this->arrayExpressions[$filter] ?? [];

            $values2 = [];

            /** @psalm-suppress MixedAssignment */
            foreach ($values as $v) {
                $values2[] = $this->query->getValueFromId(
                    class: $this->summaryClass,
                    dimension: $filter,
                    id: $v,
                );
            }

            $this->expressions[$filter] = new EqualFilter($values2);
        }
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }
}
