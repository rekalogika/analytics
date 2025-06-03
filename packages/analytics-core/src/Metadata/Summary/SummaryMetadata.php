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
use Rekalogika\Analytics\Metadata\HierarchicalDimension;
use Symfony\Contracts\Translation\TranslatableInterface;

final readonly class SummaryMetadata
{
    /**
     * All properties: dimensions, dimension properties (subdimension),
     * measures, partition.
     *
     * @var array<string,PropertyMetadata>
     */
    private array $properties;

    private PartitionMetadata $partition;

    /**
     * @var non-empty-array<string,DimensionMetadata>
     */
    private array $dimensions;

    /**
     * @var array<string,DimensionPropertyMetadata>
     */
    private array $dimensionProperties;

    /**
     * @var non-empty-array<string,DimensionMetadata|DimensionPropertyMetadata>
     */
    private array $leafDimensions;

    /**
     * @var non-empty-array<string,MeasureMetadata>
     */
    private array $measures;

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
        $allProperties = [];

        //
        // partition
        //

        $this->partition = $partition->withSummaryMetadata($this);
        $allProperties[$partition->getSummaryProperty()] = $partition;

        //
        // measures
        //

        $newMeasures = [];

        foreach ($measures as $measureKey => $measure) {
            $measure = $measure->withSummaryMetadata($this);
            $newMeasures[$measureKey] = $measure;
            $allProperties[$measureKey] = $measure;
        }

        $this->measures = $newMeasures;

        //
        // dimensions
        //

        $newDimensions = [];
        $dimensionProperties = [];
        $leafDimensions = [];

        foreach ($dimensions as $dimensionKey => $dimension) {
            $dimension = $dimension->withSummaryMetadata($this);

            $newDimensions[$dimensionKey] = $dimension;
            $allProperties[$dimensionKey] = $dimension;

            $hierarchy = $dimension->getHierarchy();

            // if not hierarchical
            if ($hierarchy === null) {
                $leafDimensions[$dimensionKey] = $dimension;

                continue;
            }

            // if hierarchical
            foreach ($dimension->getProperties() as $dimensionPropertyKey => $dimensionProperty) {
                $dimensionProperties[$dimensionPropertyKey] = $dimensionProperty;
                $allProperties[$dimensionPropertyKey] = $dimensionProperty;
                $leafDimensions[$dimensionPropertyKey] = $dimensionProperty;
            }
        }

        $this->dimensionProperties = $dimensionProperties;

        /** @var non-empty-array<string,DimensionMetadata> $newDimensions */
        $this->dimensions = $newDimensions;

        /** @var non-empty-array<string,DimensionMetadata|DimensionPropertyMetadata> $leafDimensions */
        $this->leafDimensions = $leafDimensions;

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

            foreach ($hierarchy->getProperties() as $dimensionLevelProperty) {
                $fullProperty = \sprintf(
                    '%s.%s',
                    $dimension->getSummaryProperty(),
                    $dimensionLevelProperty->getName(),
                );

                $field = new Field(
                    key: $fullProperty,
                    label: $dimension->getLabel(),
                    subLabel: $dimensionLevelProperty->getLabel(),
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

            foreach ($hierarchy->getProperties() as $dimensionLevelProperty) {
                $children[$dimensionLevelProperty->getName()] = $dimensionLevelProperty->getLabel();
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

        //
        // properties
        //

        $this->properties = $allProperties;
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
    // all properties
    //

    /**
     * @return array<string,PropertyMetadata>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $propertyName): PropertyMetadata
    {
        return $this->properties[$propertyName]
            ?? throw new MetadataException(\sprintf(
                'Property not found: %s',
                $propertyName,
            ));
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
     * Returns all the root dimensions. The DimensionPropertyMetadatas of a
     * DimensionMetadata are not included in this list.
     *
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * Returns all the leaf dimensions, which can be either a DimensionMetadata
     * or a DimensionPropertyMetadata. The DimensionMetadata of a
     * DimensionPropertyMetadata is not included in this list.
     *
     * @return non-empty-array<string,DimensionMetadata|DimensionPropertyMetadata>
     */
    public function getLeafDimensions(): array
    {
        return $this->leafDimensions;
    }

    /**
     * Returns all the dimension properties, which are subdimensions of
     * DimensionMetadata. The DimensionMetadata itself is not included in this
     * list.
     *
     * @return array<string,DimensionPropertyMetadata>
     */
    public function getDimensionProperties(): array
    {
        return $this->dimensionProperties;
    }

    /**
     * Returns all dimensions and dimension properties.
     *
     * @return array<string,DimensionMetadata|DimensionPropertyMetadata>
     */
    public function getDimensionsAndDimensionProperties(): array
    {
        return array_merge($this->dimensions, $this->dimensionProperties);
    }

    public function getDimension(string $dimensionName): DimensionMetadata
    {
        return $this->dimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }

    public function getDimensionProperty(string $propertyName): DimensionPropertyMetadata
    {
        return $this->dimensionProperties[$propertyName]
            ?? throw new MetadataException(\sprintf(
                'Dimension property not found: %s',
                $propertyName,
            ));
    }

    public function getDimensionOrDimensionProperty(
        string $dimensionName,
    ): DimensionMetadata|DimensionPropertyMetadata {
        return $this->dimensions[$dimensionName]
            ?? $this->dimensionProperties[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension or dimension property not found: %s',
                $dimensionName,
            ));
    }

    //
    // fully qualified dimensions
    //

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
    public function getMeasures(): array
    {
        return $this->measures;
    }

    public function getMeasure(string $measureName): MeasureMetadata
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
