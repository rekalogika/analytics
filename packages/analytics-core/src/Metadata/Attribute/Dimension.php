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

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Marks a property of a summary class, or of a dimension group, as a
 * dimension: a property the data is grouped by, e.g. the country of the
 * customer or the month of the order.
 *
 * ```php
 * #[ORM\ManyToOne]
 * #[Analytics\Dimension(
 *     source: new IdentifierValue('customer.country'),
 *     label: new TranslatableMessage('Customer country'),
 *     orderBy: ['name' => Order::Ascending],
 * )]
 * private ?Country $customerCountry = null;
 * ```
 *
 * If the type of the property is a class having the `DimensionGroup`
 * attribute, the property becomes a hierarchical dimension whose children are
 * the dimensions of the group. The children are referred to in queries using
 * dot notation, e.g. `time.civil.year`.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Dimension
{
    /**
     * @param ValueResolver $source How the value of the dimension is derived
     * from the source entity. For a dimension inside a dimension group, the
     * resolver must implement `DimensionGroupAware`, and derives its value
     * from the resolver of the dimension holding the group.
     * @param null|string|TranslatableInterface $label The human-readable name
     * of the dimension. Defaults to the property name.
     * @param Order|array<string,Order> $orderBy The default ordering of the
     * members of the dimension in query results, unless the query specifies
     * otherwise. An `Order` sorts by the member itself. The array form is for
     * dimensions that are relations to other entities: the keys are
     * properties of the related entity to sort by, in order of priority, and
     * the values are the directions, e.g. `['name' => Order::Ascending]`.
     * @param null|string|TranslatableInterface $nullLabel The label shown in
     * place of a null member, i.e. source entities with no value for this
     * dimension. Defaults to "(None)".
     * @param bool $hidden If true, the dimension is not offered in the user
     * interface, but can still be used in queries.
     */
    public function __construct(
        private ValueResolver $source,
        private null|string|TranslatableInterface $label = null,
        private Order|array $orderBy = Order::Ascending,
        private null|string|TranslatableInterface $nullLabel = null,
        private bool $hidden = false,
    ) {}

    /**
     * How the value of the dimension is derived from the source entity.
     */
    public function getSource(): ValueResolver
    {
        return $this->source;
    }

    /**
     * The label as given in the attribute, or null if none was given.
     */
    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    /**
     * The default ordering of the members of the dimension. See the
     * constructor for the two forms.
     *
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    /**
     * The label of a null member as given in the attribute, or null if none
     * was given.
     */
    public function getNullLabel(): null|string|TranslatableInterface
    {
        return $this->nullLabel;
    }

    /**
     * Whether the dimension is hidden from the user interface.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
