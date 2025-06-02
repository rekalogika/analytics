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

use Rekalogika\Analytics\Exception\LogicException;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelPropertyMetadata;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionPropertyMetadata extends PropertyMetadata
{
    /**
     * @param class-string|null $typeClass
     */
    public function __construct(
        private string $summaryProperty,
        private string $hierarchyProperty,
        TranslatableInterface $label,
        private TranslatableInterface $nullLabel,
        private ?string $typeClass,
        private DimensionLevelPropertyMetadata $dimensionLevelProperty,
        private ?DimensionMetadata $dimensionMetadata = null,
    ) {
        try {
            $summaryMetadata = $dimensionMetadata?->getSummaryMetadata();
        } catch (MetadataException) {
            $summaryMetadata = null;
        }

        parent::__construct(
            summaryProperty: \sprintf('%s.%s', $summaryProperty, $hierarchyProperty),
            label: $label,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function withDimensionMetadata(DimensionMetadata $dimensionMetadata): self
    {
        return new self(
            summaryProperty: $this->summaryProperty,
            hierarchyProperty: $this->hierarchyProperty,
            label: $this->getLabel(),
            nullLabel: $this->nullLabel,
            typeClass: $this->typeClass,
            dimensionLevelProperty: $this->dimensionLevelProperty,
            dimensionMetadata: $dimensionMetadata,
        );
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

    /**
     * @return null|class-string
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }
}
