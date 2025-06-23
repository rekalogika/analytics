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

final readonly class DimensionGroupMetadata
{
    /**
     * @param class-string $class
     * @param array<string,DimensionMetadata> $dimensions
     */
    public function __construct(
        private string $class,
        private GroupingStrategy $groupingStrategy,
        private array $dimensions,
    ) {}

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getGroupingStrategy(): GroupingStrategy
    {
        return $this->groupingStrategy;
    }

    /**
     * @return array<string,DimensionMetadata>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
