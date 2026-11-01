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
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Marks a Doctrine entity as a summary of another entity, the source. The
 * summary entity holds the pre-aggregated data, and its properties are
 * annotated with `Dimension`, `Measure`, `Partition`, and `Groupings` to
 * describe how the aggregation is done.
 *
 * ```php
 * #[ORM\Entity]
 * #[Analytics\Summary(
 *     sourceClass: Order::class,
 *     label: new TranslatableMessage('Orders'),
 * )]
 * class OrderSummary extends BaseSummary
 * {
 *     // ...
 * }
 * ```
 *
 * A summary class must have at least one dimension, at least one measure,
 * exactly one partition, and exactly one groupings property. Extending
 * `BaseSummary` provides the latter.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Summary
{
    /**
     * @param class-string $sourceClass The entity class being summarized.
     * @param null|string|TranslatableInterface $label The human-readable name
     * of the summary, shown in the user interface. Defaults to the short name
     * of the summary class.
     * @param GroupingStrategy|null $groupingStrategy The strategy that
     * determines which grouping sets of the root dimensions are stored in the
     * summary table. If null, the summary stores the cube of all the root
     * dimensions, i.e. every combination of every root dimension.
     */
    public function __construct(
        private string $sourceClass,
        private null|string|TranslatableInterface $label = null,
        private ?GroupingStrategy $groupingStrategy = null,
    ) {}

    /**
     * The entity class being summarized.
     *
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    /**
     * The label as given in the attribute, or null if none was given.
     */
    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    /**
     * The grouping strategy of the root dimensions, or null for the default
     * cube of every root dimension.
     */
    public function getGroupingStrategy(): ?GroupingStrategy
    {
        return $this->groupingStrategy;
    }
}
