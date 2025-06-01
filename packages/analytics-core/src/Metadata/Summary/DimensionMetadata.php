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
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionMetadata extends PropertyMetadata
{
    private ?DimensionHierarchyMetadata $hierarchy;

    /**
     * @param array<class-string,ValueResolver> $source
     * @param Order|array<string,Order> $orderBy
     * @param null|class-string $typeClass
     */
    public function __construct(
        private array $source,
        string $summaryProperty,
        TranslatableInterface $label,
        private \DateTimeZone $sourceTimeZone,
        private \DateTimeZone $summaryTimeZone,
        ?DimensionHierarchyMetadata $hierarchy,
        private Order|array $orderBy,
        private ?string $typeClass,
        private TranslatableInterface $nullLabel,
        private bool $mandatory,
        ?SummaryMetadata $summaryMetadata = null,
    ) {
        parent::__construct(
            summaryProperty: $summaryProperty,
            label: $label,
            summaryMetadata: $summaryMetadata,
        );

        if ($hierarchy !== null && \is_array($orderBy)) {
            throw new MetadataException('orderBy cannot be an array for hierarchical dimension');
        }

        $this->hierarchy = $hierarchy?->withDimensionMetadata($this);
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            source: $this->source,
            summaryProperty: $this->getSummaryProperty(),
            label: $this->getLabel(),
            sourceTimeZone: $this->sourceTimeZone,
            summaryTimeZone: $this->summaryTimeZone,
            hierarchy: $this->hierarchy,
            orderBy: $this->orderBy,
            typeClass: $this->typeClass,
            nullLabel: $this->nullLabel,
            mandatory: $this->mandatory,
            summaryMetadata: $summaryMetadata,
        );
    }

    /**
     * @return array<class-string,ValueResolver>
     */
    public function getSource(): array
    {
        return $this->source;
    }

    public function getSourceTimeZone(): \DateTimeZone
    {
        return $this->sourceTimeZone;
    }

    public function getSummaryTimeZone(): \DateTimeZone
    {
        return $this->summaryTimeZone;
    }

    public function getHierarchy(): ?DimensionHierarchyMetadata
    {
        return $this->hierarchy;
    }

    public function isHierarhical(): bool
    {
        return $this->hierarchy !== null;
    }

    /**
     * @return array<class-string,list<string>>
     */
    public function getInvolvedProperties(): array
    {
        $properties = [];

        foreach ($this->source as $class => $valueResolver) {
            foreach ($valueResolver->getInvolvedProperties() as $property) {
                $properties[$class][] = $property;
            }
        }

        $uniqueProperties = [];

        foreach ($properties as $class => $listOfProperties) {
            $uniqueProperties[$class] = array_values(array_unique($listOfProperties));
        }

        return $uniqueProperties;
    }

    /**
     * @return Order|array<string,Order>
     */
    public function getOrderBy(): Order|array
    {
        return $this->orderBy;
    }

    /**
     * @return class-string|null
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }

    public function getNullLabel(): TranslatableInterface
    {
        return $this->nullLabel;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }
}
