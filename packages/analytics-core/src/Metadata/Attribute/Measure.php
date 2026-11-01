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

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Marks a property of a summary class as a measure: an aggregated value, e.g.
 * the number of orders or the sum of their prices.
 *
 * ```php
 * #[ORM\Column(type: Types::INTEGER)]
 * #[Analytics\Measure(
 *     function: new Sum('item.price'),
 *     label: new TranslatableMessage('Price'),
 *     unit: new TranslatableMessage('USD'),
 * )]
 * private ?int $price = null;
 * ```
 *
 * A measure whose function is a `SummarizableAggregateFunction` needs a
 * mapped Doctrine column to store the aggregate. A measure that is derived
 * from other measures, or that is a `PseudoMeasure`, has no column: declare
 * it as a plain, unmapped property.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Measure
{
    /**
     * @param AggregateFunction|PseudoMeasure $function How the value of the
     * measure is computed.
     * @param null|string|TranslatableInterface $label The human-readable name
     * of the measure. Defaults to the property name.
     * @param null|string|TranslatableInterface $unit The unit of measurement,
     * e.g. a currency. Measures with the same unit can share an axis in a
     * chart.
     * @param bool $hidden If true, the measure is not offered in the user
     * interface, but can still be used in queries.
     */
    public function __construct(
        private AggregateFunction|PseudoMeasure $function,
        private null|string|TranslatableInterface $label = null,
        private null|string|TranslatableInterface $unit = null,
        private bool $hidden = false,
    ) {}

    /**
     * How the value of the measure is computed.
     */
    public function getFunction(): AggregateFunction|PseudoMeasure
    {
        return $this->function;
    }

    /**
     * The label as given in the attribute, or null if none was given.
     */
    public function getLabel(): null|string|TranslatableInterface
    {
        return $this->label;
    }

    /**
     * The unit of measurement as given in the attribute, or null if none was
     * given.
     */
    public function getUnit(): null|string|TranslatableInterface
    {
        return $this->unit;
    }

    /**
     * Whether the measure is hidden from the user interface.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }
}
