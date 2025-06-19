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

use Rekalogika\Analytics\Common\Exception\LogicException;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Common\Model\LiteralString;
use Rekalogika\Analytics\Common\Model\TranslatablePropertyDimension;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelPropertyMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionPropertyMetadata extends PropertyMetadata
{
    private ValueResolver $valueResolver;

    /**
     * @param class-string|null $typeClass
     */
    public function __construct(
        private string $summaryProperty,
        private string $hierarchyProperty,
        private TranslatableInterface $label,
        private TranslatableInterface $nullLabel,
        ?string $typeClass,
        private DimensionLevelPropertyMetadata $dimensionLevelProperty,
        AttributeCollection $attributes,
        bool $hidden,
        private ?DimensionMetadata $dimensionMetadata = null,
    ) {
        try {
            $summaryMetadata = $dimensionMetadata?->getSummaryMetadata();
        } catch (MetadataException) {
            $summaryMetadata = null;
        }

        // value resolver

        $valueResolver = $dimensionLevelProperty->getValueResolver();

        if ($dimensionMetadata !== null) {
            $valueResolver = $valueResolver
                ->withInput($dimensionMetadata->getValueResolver());
        }

        $this->valueResolver = $valueResolver;

        // label

        $label = new TranslatablePropertyDimension(
            propertyLabel: $dimensionMetadata?->getLabel() ?? new LiteralString('Unknown'),
            dimensionLabel: $label,
        );

        parent::__construct(
            name: \sprintf('%s.%s', $summaryProperty, $hierarchyProperty),
            label: $label,
            typeClass: $typeClass,
            hidden: $hidden,
            attributes: $attributes,
            involvedSourceProperties: $dimensionMetadata?->getInvolvedSourceProperties() ?? [],
            summaryMetadata: $summaryMetadata,
        );
    }

    public function withDimensionMetadata(DimensionMetadata $dimensionMetadata): self
    {
        return new self(
            summaryProperty: $this->summaryProperty,
            hierarchyProperty: $this->hierarchyProperty,
            label: $this->label,
            nullLabel: $this->nullLabel,
            typeClass: $this->getTypeClass(),
            dimensionLevelProperty: $this->dimensionLevelProperty,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            dimensionMetadata: $dimensionMetadata,
        );
    }

    public function getSummaryProperty(): string
    {
        return $this->summaryProperty;
    }

    public function getHierarchyProperty(): string
    {
        return $this->hierarchyProperty;
    }

    public function getDimension(): DimensionMetadata
    {
        if (null === $this->dimensionMetadata) {
            throw new LogicException('Dimension metadata is not set.');
        }

        return $this->dimensionMetadata;
    }

    public function getDimensionLevelProperty(): DimensionLevelPropertyMetadata
    {
        return $this->dimensionLevelProperty;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    public function getValueResolver(): ValueResolver
    {
        return $this->valueResolver;
    }
}
