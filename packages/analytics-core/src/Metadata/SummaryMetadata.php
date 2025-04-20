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

final readonly class SummaryMetadata
{
    /**
     * @var non-empty-array<string,DimensionMetadata>
     */
    private array $dimensions;

    /**
     * @var array<string,FullyQualifiedDimensionMetadata>
     */
    private array $fullyQualifiedDimensions;


    /**
     * @var array<string,FullyQualifiedPropertyMetadata>
     */
    private array $fullyQualifiedProperties;

    /**
     * @param non-empty-list<class-string> $sourceClasses
     * @param class-string $summaryClass
     * @param non-empty-array<string,DimensionMetadata> $dimensions
     * @param non-empty-array<string,MeasureMetadata> $measures
     */
    public function __construct(
        private array $sourceClasses,
        private string $summaryClass,
        private PartitionMetadata $partition,
        array $dimensions,
        private array $measures,
        private string $groupingsProperty,
        private TranslatableInterface $label,
    ) {
        // process dimensions

        $fullyQualifiedDimensions = [];
        $newDimensions = [];

        foreach ($dimensions as $dimensionKey => $dimension) {
            $newDimensions[$dimensionKey] = $dimension->withSummaryMetadata($this);

            $hierarchy = $dimension->getHierarchy();

            if ($hierarchy === null) {
                $fullyQualifiedDimension = new FullyQualifiedDimensionMetadata(
                    dimension: $dimension,
                    dimensionProperty: null,
                    summaryMetadata: $this,
                );

                $fullyQualifiedDimensions[$fullyQualifiedDimension->getFullName()] = $fullyQualifiedDimension;
            } else {
                foreach ($hierarchy->getProperties() as $property) {
                    $fullyQualifiedDimension = new FullyQualifiedDimensionMetadata(
                        dimension: $dimension,
                        dimensionProperty: $property,
                        summaryMetadata: $this,
                    );

                    $fullyQualifiedDimensions[$fullyQualifiedDimension->getFullName()] = $fullyQualifiedDimension;
                }
            }
        }

        $this->dimensions = $newDimensions;
        $this->fullyQualifiedDimensions = $fullyQualifiedDimensions;

        // process fully qualified properties

        $fullyQualifiedProperties = [];

        foreach ($this->fullyQualifiedDimensions as $dimension) {
            $fullyQualifiedProperties[$dimension->getFullName()] =
                new FullyQualifiedPropertyMetadata(
                    property: $dimension,
                    summaryMetadata: $this,
                );
        }

        foreach ($this->measures as $measure) {
            $fullyQualifiedProperties[$measure->getSummaryProperty()] =
                new FullyQualifiedPropertyMetadata(
                    property: $measure,
                    summaryMetadata: $this,
                );
        }

        $this->fullyQualifiedProperties = $fullyQualifiedProperties;
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * @return non-empty-list<class-string>
     */
    public function getSourceClasses(): array
    {
        return $this->sourceClasses;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function getPartition(): PartitionMetadata
    {
        return $this->partition;
    }

    /**
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getDimensionMetadatas(): array
    {
        return $this->dimensions;
    }

    public function getDimensionMetadata(string $dimensionName): DimensionMetadata
    {
        return $this->dimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }

    public function isDimensionMandatory(string $dimensionName): bool
    {
        return $this->getDimensionMetadata($dimensionName)->isMandatory();
    }

    /**
     * @return non-empty-array<string,MeasureMetadata>
     */
    public function getMeasureMetadatas(): array
    {
        return $this->measures;
    }

    public function getMeasureMetadata(string $measureName): MeasureMetadata
    {
        return $this->measures[$measureName]
            ?? throw new MetadataException(\sprintf(
                'Measure not found: %s',
                $measureName,
            ));
    }

    public function getFieldMetadata(string $fieldName): DimensionMetadata|MeasureMetadata
    {
        if (!str_contains($fieldName, '.')) {
            return $this->dimensions[$fieldName]
                ?? $this->measures[$fieldName]
                ?? throw new MetadataException(\sprintf(
                    'Field not found: %s',
                    $fieldName,
                ));
        }

        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        [$dimensionName, $propertyName] = explode('.', $fieldName, 2);

        return $this->dimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }

    public function getFullyQualifiedDimension(string $dimensionName): FullyQualifiedDimensionMetadata
    {
        return $this->fullyQualifiedDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf('Dimension not found: %s', $dimensionName));
    }

    public function getFullyQualifiedProperty(string $propertyName): FullyQualifiedPropertyMetadata
    {
        return $this->fullyQualifiedProperties[$propertyName]
            ?? throw new MetadataException(\sprintf('Property not found: %s', $propertyName));
    }

    /**
     * @return list<string>
     */
    public function getDimensionPropertyNames(): array
    {
        return array_keys($this->fullyQualifiedDimensions);
    }

    public function isMeasure(string $fieldName): bool
    {
        return isset($this->measures[$fieldName]);
    }

    public function isDimension(string $fieldName): bool
    {
        return isset($this->dimensions[$fieldName]);
    }

    public function getGroupingsProperty(): string
    {
        return $this->groupingsProperty;
    }

    /**
     * Source class to the list of its properties that influence this summary.
     *
     * @return array<class-string,list<string>>
     */
    public function getInvolvedProperties(): array
    {
        $properties = [];
        $dimensionsAndMeasures = array_merge($this->dimensions, $this->measures);

        foreach ($dimensionsAndMeasures as $dimensionOrMeasure) {
            foreach ($dimensionOrMeasure->getInvolvedProperties() as $class => $dimensionOrMeasureProperties) {
                foreach ($dimensionOrMeasureProperties as $property) {
                    // normalize property
                    // - remove everything after dot
                    $property = explode('.', $property)[0];
                    // - remove everything after (
                    $property = explode('(', $property)[0];
                    // - remove * from the beginning
                    $property = ltrim($property, '*');

                    $properties[$class][] = $property;
                }
            }
        }

        $uniqueProperties = [];

        foreach ($properties as $class => $listOfProperties) {
            $uniqueProperties[$class] = array_values(array_unique($listOfProperties));
        }

        return $uniqueProperties;
    }
}
