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

namespace Rekalogika\Analytics\Bundle\UI;

use Rekalogika\Analytics\SummaryManager\SummaryQuery;

/**
 * @implements \IteratorAggregate<string,Filter>
 */
final class Filters implements \IteratorAggregate
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
        private FilterFactory $filterFactory,
    ) {
        $this->initializeFilters($dimensions);
    }

    /**
     * @var array<string,Filter>
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
            /** @psalm-suppress MixedAssignment */
            $filterArray = $this->arrayExpressions[$filter] ?? [];

            if (!\is_array($filterArray)) {
                $filterArray = [];
            }

            /** @var array<string,mixed> $filterArray */

            $this->expressions[$filter] = $this->filterFactory
                ->createFilter(
                    summaryClass: $this->summaryClass,
                    dimension: $filter,
                    inputArray: $filterArray,
                );
        }
    }

    public function applyToQuery(SummaryQuery $query): void
    {
        foreach ($this->expressions as $filterExpression) {
            $expression = $filterExpression->createExpression();

            if ($expression !== null) {
                $query->andWhere($expression);
            }
        }
    }
}
