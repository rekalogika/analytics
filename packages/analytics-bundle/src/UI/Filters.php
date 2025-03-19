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

/**
 * @implements \IteratorAggregate<string,Filter>
 */
final readonly class Filters implements \IteratorAggregate
{
    /**
     * @var array<string,Filter>
     */
    private array $filters;

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
        $this->filters = $this->initializeFilters($dimensions);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }

    /**
     * @param list<string> $dimensions
     * @return array<string,Filter>
     */
    private function initializeFilters(array $dimensions): array
    {
        $filters = [];

        foreach ($dimensions as $dimension) {
            /** @psalm-suppress MixedAssignment */
            $filterArray = $this->arrayExpressions[$dimension] ?? [];

            if (!\is_array($filterArray)) {
                $filterArray = [];
            }

            /** @var array<string,mixed> $filterArray */

            $filters[$dimension] = $this->filterFactory
                ->createFilter(
                    summaryClass: $this->summaryClass,
                    dimension: $dimension,
                    inputArray: $filterArray,
                );
        }

        return $filters;
    }
}
