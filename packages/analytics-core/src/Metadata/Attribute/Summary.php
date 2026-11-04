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

use Doctrine\Common\Collections\Expr\Expression;
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
     * @param bool $trackAssociations Whether a change to a related entity
     * read through an association, e.g. `category` of the `Item` in
     * `item.category`, refreshes the source entities that refer to it. Set to
     * false if the related entities change often and refer to many source
     * entities, and use `SummaryManager::markDirty()` where needed instead.
     * @param Expression|null $filter Restricts the source entities that are
     * summarized, e.g. to exclude cancelled orders. A Doctrine Collections
     * `Comparison` of a property path of the source entity with a constant,
     * e.g. `new Comparison('status', '<>', 'cancelled')`, or
     * `new Comparison('customer.country.id', 'IN', ['ID', 'SG'])`. Several
     * conditions can be combined with a `CompositeExpression`, and a
     * condition that is not a comparison can be written in DQL with
     * `SourceCondition`. The paths the filter reads are tracked like the ones
     * of the dimensions and measures. A comparison through an association
     * excludes the source entities whose association is null, unless the
     * filter allows null explicitly. A path through a to-many association is
     * rejected, because the join would summarize a source entity more than
     * once. Use a `SourceCondition` with an `EXISTS` subquery instead.
     */
    public function __construct(
        private string $sourceClass,
        private null|string|TranslatableInterface $label = null,
        private ?GroupingStrategy $groupingStrategy = null,
        private bool $trackAssociations = true,
        private ?Expression $filter = null,
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

    /**
     * Whether a change to a related entity refreshes the source entities that
     * refer to it.
     */
    public function isTrackAssociations(): bool
    {
        return $this->trackAssociations;
    }

    /**
     * The condition restricting the source entities that are summarized, or
     * null if every source entity is summarized.
     */
    public function getFilter(): ?Expression
    {
        return $this->filter;
    }
}
