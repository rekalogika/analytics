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

use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\Field;
use Rekalogika\Analytics\Metadata\FullyQualifiedDimensionMetadata;
use Rekalogika\Analytics\Metadata\FullyQualifiedPropertyMetadata;
use Rekalogika\Analytics\Metadata\HierarchicalDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class SummaryMetadata
{
    private PartitionMetadata $partition;

    /**
     * @var non-empty-array<string,DimensionMetadata>
     */
    private array $dimensions;

    /**
     * @var non-empty-array<string,MeasureMetadata>
     */
    private array $measures;

    /**
     * @var array<string,FullyQualifiedDimensionMetadata>
     */
    private array $fullyQualifiedDimensions;


    /**
     * @var array<string,FullyQualifiedPropertyMetadata>
     */
    private array $fullyQualifiedProperties;

    /**
     * @var array<string,Field>
     */
    private array $dimensionChoices;

    /**
     * @var array<string,Field>
     */
    private array $measureChoices;

    /**
     * @var non-empty-array<string,TranslatableInterface|iterable<string,TranslatableInterface>>
     */
    private array $hierarchicalDimensionChoices;

    /**
     * @param non-empty-list<class-string> $sourceClasses
     * @param class-string $summaryClass
     * @param non-empty-array<string,DimensionMetadata> $dimensions
     * @param non-empty-array<string,MeasureMetadata> $measures
     */
    public function __construct(
        private array $sourceClasses,
        private string $summaryClass,
        PartitionMetadata $partition,
        array $dimensions,
        array $measures,
        private string $groupingsProperty,
        private TranslatableInterface $label,
    ) {
        //
        // partition
        //

        $this->partition = $partition->withSummaryMetadata($this);

        //
        // measures
        //

        $newMeasures = [];

        foreach ($measures as $measureKey => $measure) {
            $newMeasures[$measureKey] = $measure->withSummaryMetadata($this);
        }

        $this->measures = $newMeasures;

        //
        // dimensions
        //

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

        //
        // fully qualified properties
        //

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

        //
        // dimension choices
        //

        $dimensionChoices = [];

        foreach ($dimensions as $dimension) {
            $hierarchy = $dimension->getHierarchy();

            // if not hierarchical

            if ($hierarchy === null) {
                $field = new Field(
                    key: $dimension->getSummaryProperty(),
                    label: $dimension->getLabel(),
                    subLabel: null,
                );

                $dimensionChoices[$field->getKey()] = $field;

                continue;
            }

            // if hierarchical

            foreach ($hierarchy->getProperties() as $property) {
                $fullProperty = \sprintf(
                    '%s.%s',
                    $dimension->getSummaryProperty(),
                    $property->getName(),
                );

                $field = new Field(
                    key: $fullProperty,
                    label: $dimension->getLabel(),
                    subLabel: $property->getLabel(),
                );

                $dimensionChoices[$field->getKey()] = $field;
            }
        }

        $this->dimensionChoices = $dimensionChoices;

        //
        // hierarchical dimension choices
        //

        $hierarchicalDimensionChoices = [];

        foreach ($dimensions as $dimensionMetadata) {
            $hierarchy = $dimensionMetadata->getHierarchy();

            // if not hierarchical

            if ($hierarchy === null) {
                $hierarchicalDimensionChoices[$dimensionMetadata->getSummaryProperty()] = $dimensionMetadata->getLabel();

                continue;
            }

            // if hierarchical

            $children = [];

            foreach ($hierarchy->getProperties() as $property) {
                $children[$property->getName()] = $property->getLabel();
            }

            $hierarchicalDimensionChoices[$dimensionMetadata->getSummaryProperty()] =
                new HierarchicalDimension(
                    label: $dimensionMetadata->getLabel(),
                    children: $children,
                );
        }

        /** @var non-empty-array<string,TranslatableInterface|iterable<string,TranslatableInterface>> $hierarchicalDimensionChoices */

        $this->hierarchicalDimensionChoices = $hierarchicalDimensionChoices;

        //
        // measure choices
        //

        $measureChoices = [];

        foreach ($measures as $measureMetadata) {
            $field = new Field(
                key: $measureMetadata->getSummaryProperty(),
                label: $measureMetadata->getLabel(),
                subLabel: null,
            );

            $measureChoices[$field->getKey()] = $field;
        }

        $this->measureChoices = $measureChoices;
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

    //
    // partition
    //

    public function getPartition(): PartitionMetadata
    {
        return $this->partition;
    }

    public function getGroupingsProperty(): string
    {
        return $this->groupingsProperty;
    }

    //
    // dimensions
    //

    /**
     * @return list<string>
     */
    public function getDimensionPropertyNames(): array
    {
        return array_keys($this->fullyQualifiedDimensions);
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

    public function getFullyQualifiedDimension(
        string $dimensionName,
    ): FullyQualifiedDimensionMetadata {
        return $this->fullyQualifiedDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf('Dimension not found: %s', $dimensionName));
    }

    /**
     * @return array<string,Field>
     */
    public function getDimensionChoices(): array
    {
        return $this->dimensionChoices;
    }

    /**
     * @return non-empty-array<string,TranslatableInterface|iterable<string,TranslatableInterface>>
     */
    public function getHierarchicalDimensionChoices(): array
    {
        return $this->hierarchicalDimensionChoices;
    }

    //
    // measures
    //

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

    public function isMeasure(string $fieldName): bool
    {
        return isset($this->measures[$fieldName]);
    }

    /**
     * @return array<string,Field>
     */
    public function getMeasureChoices(): array
    {
        return $this->measureChoices;
    }

    //
    // field (dimension or measure)
    //

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

    public function getFullyQualifiedField(string $propertyName): FullyQualifiedPropertyMetadata
    {
        return $this->fullyQualifiedProperties[$propertyName]
            ?? throw new MetadataException(\sprintf('Property not found: %s', $propertyName));
    }

    //
    // sources
    //

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
