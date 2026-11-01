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

namespace Rekalogika\Analytics\Metadata\Summary;

use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;

/**
 * Metadata of a dimension group class, independent of where the group is
 * used. When a dimension holds the group, its grouping strategy and
 * dimensions become the dimension's grouping strategy and children.
 */
final readonly class DimensionGroupMetadata
{
    /**
     * @param class-string $class The dimension group class.
     * @param GroupingStrategy $groupingStrategy From the `DimensionGroup`
     * attribute.
     * @param array<string,DimensionMetadata> $dimensions The dimensions of
     * the group, keyed by property name.
     */
    public function __construct(
        private string $class,
        private GroupingStrategy $groupingStrategy,
        private array $dimensions,
    ) {}

    /**
     * The dimension group class.
     *
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Determines which combinations of the group's dimensions are stored.
     */
    public function getGroupingStrategy(): GroupingStrategy
    {
        return $this->groupingStrategy;
    }

    /**
     * The dimensions of the group, keyed by property name.
     *
     * @return array<string,DimensionMetadata>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
