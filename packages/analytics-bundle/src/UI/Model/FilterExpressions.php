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
use Rekalogika\Analytics\TimeInterval;

/**
 * @implements \IteratorAggregate<string,FilterExpression>
 */
final class FilterExpressions implements \IteratorAggregate
{
    /**
     * @param list<string> $dimensions
     * @param array<string,mixed> $arrayExpressions
     */
    public function __construct(
        array $dimensions,
        private array $arrayExpressions,
        private SummaryQuery $query,
        private Stringifier $stringifier,
    ) {
        $this->initializeFilters($dimensions);
    }

    /**
     * @var array<string,FilterExpression>
     */
    private array $expressions = [];

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->expressions);
    }

    /**
     * @param list<string> $filters
     */
    private function initializeFilters(array $filters): void
    {
        foreach ($filters as $filter) {
            $filterArray = $this->arrayExpressions[$filter] ?? [];

            if (!\is_array($filterArray)) {
                throw new \InvalidArgumentException('Invalid filter array');
            }

            /** @var array<string,mixed> $filterArray */

            $typeClass = $this->query->getMetadata()->getDimensionTypeClass($filter);

            if ($typeClass === null) {
                $filterExpression = $this->createEqualFilter($filter, $filterArray);
            } elseif (is_a($typeClass, TimeInterval::class, true)) {
                $filterExpression = $this->createDateRangeFilter($filter, $filterArray, $typeClass);
            } else {
                $filterExpression = $this->createEqualFilter($filter, $filterArray);
            }

            $this->expressions[$filter] = $filterExpression;
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
     * @param array<string,mixed> $input
     * @param class-string<TimeInterval> $typeClass
     */
    private function createDateRangeFilter(
        string $dimension,
        array $input,
        string $typeClass,
    ): DateRangeFilter {
        return new DateRangeFilter(
            query: $this->query,
            dimension: $dimension,
            typeClass: $typeClass,
            inputArray: $input,
        );
    }

    public function applyToQuery(): void
    {
        foreach ($this->expressions as $expression) {
            $this->query->andWhere($expression->createExpression());
        }
    }
}
