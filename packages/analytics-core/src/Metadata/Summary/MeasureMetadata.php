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

use Rekalogika\Analytics\Contracts\Summary\AggregateFunction;
use Rekalogika\Analytics\Contracts\Summary\PseudoMeasure;
use Rekalogika\Analytics\Contracts\Summary\SummarizableAggregateFunction;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Metadata of a measure of a summary class.
 */
final readonly class MeasureMetadata extends PropertyMetadata
{
    /**
     * @param AggregateFunction|PseudoMeasure $function How the value is
     * computed.
     * @param string $summaryProperty The name of the PHP property.
     * @param TranslatableInterface $label The human-readable name.
     * @param class-string|null $typeClass The class of the property type.
     * @param TranslatableInterface|null $unit The unit of measurement.
     * @param string|null $unitSignature Identifies the unit, so measures with
     * the same unit can be recognized. Null if there is no unit.
     * @param bool $virtual Whether the measure has no column in the summary
     * table.
     * @param bool $hidden Whether the measure is hidden from the user
     * interface.
     * @param AttributeCollection $attributes The attributes of the property.
     * @param SummaryMetadata|null $summaryMetadata The owning summary.
     */
    public function __construct(
        private AggregateFunction|PseudoMeasure $function,
        string $summaryProperty,
        TranslatableInterface $label,
        ?string $typeClass,
        private null|TranslatableInterface $unit,
        private ?string $unitSignature,
        private bool $virtual,
        bool $hidden,
        AttributeCollection $attributes,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        if ($function instanceof SummarizableAggregateFunction) {
            $involvedSourceProperties = $function->getInvolvedProperties();
        } else {
            $involvedSourceProperties = [];
        }

        parent::__construct(
            name: $summaryProperty,
            propertyName: $summaryProperty,
            label: $label,
            typeClass: $typeClass,
            hidden: $hidden,
            attributes: $attributes,
            involvedSourceProperties: $involvedSourceProperties,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * Returns a copy of this measure attached to the given summary. Called by
     * `SummaryMetadata`.
     */
    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            function: $this->function,
            summaryProperty: $this->getName(),
            label: $this->getLabel(),
            typeClass: $this->getTypeClass(),
            unit: $this->unit,
            unitSignature: $this->unitSignature,
            virtual: $this->virtual,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * How the value of the measure is computed.
     */
    public function getFunction(): AggregateFunction|PseudoMeasure
    {
        return $this->function;
    }

    /**
     * The unit of measurement, or null if the measure has none.
     */
    public function getUnit(): null|TranslatableInterface
    {
        return $this->unit;
    }

    /**
     * A string identifying the unit. Two measures with the same signature
     * have the same unit, e.g. they can share an axis in a chart. Null if the
     * measure has no unit.
     */
    public function getUnitSignature(): ?string
    {
        return $this->unitSignature;
    }

    /**
     * Returns true if the measure has no column in the summary table. This is
     * the case for a measure derived from other measures, and for a
     * pseudo-measure. The refresh engine skips virtual measures.
     */
    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    /**
     * Returns true if the measure is computed in PHP by a `PseudoMeasure`
     * rather than by a DQL expression.
     */
    public function isPseudoMeasure(): bool
    {
        return $this->function instanceof PseudoMeasure;
    }
}
