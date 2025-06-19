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

use Doctrine\Common\Collections\Order;
use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionMetadata extends PropertyMetadata
{
    /**
     * @var array<string,DimensionPropertyMetadata>
     */
    private array $properties;

    /**
     * @param Order|array<string,Order> $orderBy
     * @param null|class-string $typeClass
     * @param array<string,DimensionPropertyMetadata> $properties
     */
    public function __construct(
        string $summaryProperty,
        private ValueResolver $valueResolver,
        TranslatableInterface $label,
        private ?DimensionHierarchyMetadata $hierarchy,
        private Order|array $orderBy,
        ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private bool $mandatory,
        bool $hidden,
        AttributeCollection $attributes,
        array $properties,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            name: $summaryProperty,
            label: $label,
            typeClass: $typeClass,
            hidden: $hidden,
            attributes: $attributes,
            involvedSourceProperties: $valueResolver->getInvolvedProperties(),
            summaryMetadata: $summaryMetadata,
        );

        // hierarchy

        if ($hierarchy !== null && \is_array($orderBy)) {
            throw new MetadataException('orderBy cannot be an array for hierarchical dimension');
        }

        // properties

        $newProperties = [];

        foreach ($properties as $property) {
            $newProperties[$property->getName()] = $property
                ->withDimensionMetadata($this);
        }

        $this->properties = $newProperties;
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            summaryProperty: $this->getName(),
            valueResolver: $this->valueResolver,
            label: $this->getLabel(),
            hierarchy: $this->hierarchy,
            orderBy: $this->orderBy,
            typeClass: $this->getTypeClass(),
            nullLabel: $this->nullLabel,
            mandatory: $this->mandatory,
            hidden: $this->isHidden(),
            attributes: $this->getAttributes(),
            summaryMetadata: $summaryMetadata,
            properties: $this->properties,
        );
    }

    public function getValueResolver(): ValueResolver
    {
        return $this->valueResolver;
    }

    public function isHierarchical(): bool
    {
        return $this->hierarchy !== null;
    }

    /**
     * @deprecated
     */
    public function getHierarchy(): ?DimensionHierarchyMetadata
    {
        return $this->hierarchy;
    }

    /**
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    /**
     * @todo deprecate this?
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @return array<string,DimensionPropertyMetadata>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
