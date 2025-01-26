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

use Rekalogika\Analytics\ValueResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class DimensionMetadata
{
    private ?DimensionHierarchyMetadata $hierarchy;

    /**
     * @param array<class-string,ValueResolver> $source
     */
    public function __construct(
        private array $source,
        private string $summaryProperty,
        private string|TranslatableInterface $label,
        private \DateTimeZone $sourceTimeZone,
        private \DateTimeZone $summaryTimeZone,
        ?DimensionHierarchyMetadata $hierarchy,
        private ?SummaryMetadata $summaryMetadata = null,
    ) {
        $this->hierarchy = $hierarchy?->withDimensionMetadata($this);
    }

    public function withSummaryMetadata(SummaryMetadata $summaryMetadata): self
    {
        return new self(
            source: $this->source,
            summaryProperty: $this->summaryProperty,
            label: $this->label,
            sourceTimeZone: $this->sourceTimeZone,
            summaryTimeZone: $this->summaryTimeZone,
            hierarchy: $this->hierarchy,
            summaryMetadata: $summaryMetadata,
        );
    }

    public function getSummaryMetadata(): SummaryMetadata
    {
        if ($this->summaryMetadata === null) {
            throw new \LogicException('Summary table metadata is not set');
        }

        return $this->summaryMetadata;
    }

    /**
     * @return array<class-string,ValueResolver>
     */
    public function getSource(): array
    {
        return $this->source;
    }

    public function getSummaryProperty(): string
    {
        return $this->summaryProperty;
    }

    public function getLabel(): string|TranslatableInterface
    {
        return $this->label;
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
}
