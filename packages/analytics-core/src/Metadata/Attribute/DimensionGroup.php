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

namespace Rekalogika\Analytics\Metadata\Attribute;

use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DimensionGroup
{
    public function __construct(
        private GroupingStrategy $groupingStrategy,
    ) {}

    public function getGroupingStrategy(): GroupingStrategy
    {
        return $this->groupingStrategy;
    }
}
