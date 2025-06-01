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

namespace Rekalogika\Analytics\Metadata;

use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelPropertyMetadata;
use Rekalogika\Analytics\Metadata\Summary\DimensionMetadata;
use Rekalogika\Analytics\Metadata\Summary\SummaryMetadata;
use Rekalogika\Analytics\Util\TranslatablePropertyDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class FullyQualifiedDimensionMetadata
{
    /**
     */
    public function __construct(
        private DimensionMetadata $dimension,
        private ?DimensionLevelPropertyMetadata $dimensionLevelProperty,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            dimension: $this->dimension,
            dimensionLevelProperty: $this->dimensionLevelProperty,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getFullName(): string
    {
        if ($this->dimensionLevelProperty === null) {
            return $this->dimension->getSummaryProperty();
        }

        return \sprintf(
            '%s.%s',
            $this->dimension->getSummaryProperty(),
            $this->dimensionLevelProperty->getName(),
        );
    }

    public function getLabel(): TranslatableInterface
    {
        if ($this->dimensionLevelProperty === null) {
            return $this->dimension->getLabel();
        }

        return new TranslatablePropertyDimension(
            propertyLabel: $this->dimension->getLabel(),
            dimensionLabel: $this->dimensionLevelProperty->getLabel(),
        );
    }

    /**
     * @return null|class-string
     */
    public function getTypeClass(): ?string
    {
        if ($this->dimensionLevelProperty === null) {
            return $this->dimension->getTypeClass();
        }

        return $this->dimensionLevelProperty->getTypeClass();
    }

    public function getDimension(): DimensionMetadata
    {
        return $this->dimension;
    }

    public function getDimensionProperty(): DimensionLevelPropertyMetadata
    {
        if ($this->dimensionLevelProperty === null) {
            throw new MetadataException('Dimension property is not set');
        }

        return $this->dimensionLevelProperty;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new MetadataException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }

    public function getNullLabel(): TranslatableInterface
    {
        if ($this->dimensionLevelProperty === null) {
            return $this->dimension->getNullLabel();
        }

        return $this->dimensionLevelProperty->getNullLabel();
    }
}
