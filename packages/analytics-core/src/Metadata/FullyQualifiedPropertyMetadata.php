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
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class FullyQualifiedPropertyMetadata
{
    /**
     */
    public function __construct(
        private FullyQualifiedDimensionMetadata|MeasureMetadata $property,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {}

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            property: $this->property,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getFullName(): string
    {
        if ($this->property instanceof FullyQualifiedDimensionMetadata) {
            return $this->property->getFullName();
        }

        return $this->property->getSummaryProperty();
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->property->getLabel();
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new MetadataException('Summary metadata is not set');
        }

        return $this->summaryMetadata;
    }
}
