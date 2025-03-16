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

use Rekalogika\Analytics\Util\TranslatablePropertyDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class FullyQualifiedDimensionMetadata
{
    /**
     */
    public function __construct(
        private DimensionMetadata $dimension,
        private ?DimensionPropertyMetadata $dimensionProperty = null,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            dimension: $this->dimension,
            dimensionProperty: $this->dimensionProperty,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getFullName(): string
    {
        if ($this->dimensionProperty === null) {
            return $this->dimension->getSummaryProperty();
        }

        return $this->dimensionProperty->getFullName();
    }

    public function getLabel(): string|TranslatableInterface
    {
        if ($this->dimensionProperty === null) {
            return $this->dimension->getLabel();
        }

        return new TranslatablePropertyDimension(
            propertyLabel: $this->dimension->getLabel(),
            dimensionLabel: $this->dimensionProperty->getLabel(),
        );
    }

    /**
     * @return null|class-string
     */
    public function getTypeClass(): ?string
    {
        if ($this->dimensionProperty === null) {
            return $this->dimension->getTypeClass();
        }

        return $this->dimensionProperty->getTypeClass();
    }

    public function getDimension(): DimensionMetadata
    {
        return $this->dimension;
    }

    public function getDimensionProperty(): DimensionPropertyMetadata
    {
        if ($this->dimensionProperty === null) {
            throw new \LogicException('Dimension property is not set');
        }

        return $this->dimensionProperty;
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new \LogicException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }
}
