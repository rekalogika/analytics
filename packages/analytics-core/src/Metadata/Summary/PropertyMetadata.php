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

use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Common base of the metadata of the properties of a summary class:
 * dimensions, measures, and the partition. Translating the metadata gives the
 * label of the property.
 */
abstract readonly class PropertyMetadata implements TranslatableInterface
{
    /**
     * @param string $name The fully qualified name, e.g. `time.hour`.
     * @param string $propertyName The name of the PHP property, e.g. `hour`.
     * @param TranslatableInterface $label The human-readable name.
     * @param class-string|null $typeClass The class of the property type, or
     * null for scalar and untyped properties.
     * @param bool $hidden Whether the property is hidden from the user
     * interface.
     * @param AttributeCollection $attributes The attributes of the property.
     * @param list<string> $involvedSourceProperties The properties of the
     * source entity this property is computed from.
     * @param SummaryMetadata|null $summaryMetadata The owning summary, set by
     * the summary metadata when it takes ownership of the property.
     */
    protected function __construct(
        private string $name,
        private string $propertyName,
        private TranslatableInterface $label,
        private ?string $typeClass,
        private bool $hidden,
        private AttributeCollection $attributes,
        private array $involvedSourceProperties,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    #[\Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->label->trans($translator, $locale);
    }

    /**
     * Fully qualified property name. e.g. `time.hour`
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Property name without parent name. e.g. `hour`
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * The metadata of the summary class this property belongs to.
     *
     * @throws MetadataException If the property has not been attached to a
     * summary yet.
     */
    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new MetadataException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }

    /**
     * The attributes of the property, including custom ones.
     */
    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
    }

    /**
     * The class, interface, or enum of the property type, or null if the
     * property is scalar, untyped, or has a union type. For a dimension that
     * is a relation, this is the related entity class. For a dimension group,
     * the group class.
     *
     * @return class-string|null
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }

    /**
     * The human-readable name of the property.
     */
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    /**
     * Whether the property is hidden from the user interface. Hidden
     * properties can still be used in queries.
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * The properties of the source entity this property is computed from, as
     * declared by its value resolver or aggregate function. Property paths
     * are returned as declared, e.g. `customer.country`.
     *
     * @return list<string>
     */
    public function getInvolvedSourceProperties(): array
    {
        return $this->involvedSourceProperties;
    }
}
