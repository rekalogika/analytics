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

/**
 * Marks a Doctrine embeddable as a dimension group: a reusable set of related
 * dimensions, e.g. the year, month, and day of a date. A summary class gets
 * all of the group's dimensions at once by declaring a property of the
 * group's type with the `Dimension` attribute. Groups can be nested.
 *
 * ```php
 * #[ORM\Embeddable]
 * #[Analytics\DimensionGroup(groupingStrategy: new GroupingSetStrategy())]
 * class TimeGroup extends BaseDimensionGroup
 * {
 *     #[ORM\Embedded]
 *     #[Analytics\Dimension(source: new Noop(), label: 'Civil')]
 *     private ?GregorianDateWithHour $civil = null;
 *
 *     // ...
 * }
 * ```
 *
 * The value resolvers of the dimensions inside the group must implement
 * `DimensionGroupAware`, because they derive their values from the resolver
 * of the dimension holding the group.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DimensionGroup
{
    /**
     * @param GroupingStrategy $groupingStrategy Determines which combinations
     * of the group's dimensions are stored in the summary table, e.g. every
     * combination (a cube), or only the hierarchical prefixes (a roll-up).
     */
    public function __construct(
        private GroupingStrategy $groupingStrategy,
    ) {}

    /**
     * The grouping strategy of the dimensions in the group.
     */
    public function getGroupingStrategy(): GroupingStrategy
    {
        return $this->groupingStrategy;
    }
}
