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

use Rekalogika\Analytics\Common\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\Attribute\AttributeCollection;
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
     * Source class to the list of its properties that influence this summary.
     *
     * @var list<string>
     */
    private array $involvedSourceProperties;

    /**
     * @param class-string $sourceClass
     * @param class-string $summaryClass
     * @param non-empty-array<string,DimensionMetadata> $dimensions
     * @param non-empty-array<string,MeasureMetadata> $measures
     */
    public function __construct(
        private string $sourceClass,
        private string $summaryClass,
        PartitionMetadata $partition,
        array $dimensions,
        array $measures,
        private string $groupingsProperty,
        private AttributeCollection $attributes,
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
        // all properties
        //

        $this->properties = $allProperties;

        //
        // involved properties
        //

        $properties = [];
        $dimensionsAndMeasures = [
            ...$this->dimensions,
            ...$this->measures,
        ];

        foreach ($dimensionsAndMeasures as $dimensionOrMeasure) {
            foreach ($dimensionOrMeasure->getInvolvedSourceProperties() as $property) {
                // normalize property
                // - remove everything after dot
                $property = explode('.', $property)[0];
                // - remove everything after (
                $property = explode('(', $property)[0];

                $properties[] = $property;
            }
        }

        $this->involvedSourceProperties = array_values(array_unique($properties));
    }

    /**
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
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
    public function getRootDimensions(): array
    {
        return $this->dimensions;
    }

    public function getRootDimension(string $dimensionName): DimensionMetadata
    {
        return $this->dimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
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

    public function getLeafDimension(
        string $dimensionName,
    ): DimensionMetadata|DimensionPropertyMetadata {
        return $this->leafDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Leaf dimension not found: %s',
                $dimensionName,
            ));
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

    public function getDimensionProperty(string $propertyName): DimensionPropertyMetadata
    {
        return $this->dimensionProperties[$propertyName]
            ?? throw new MetadataException(\sprintf(
                'Dimension property not found: %s',
                $propertyName,
            ));
    }

    public function getAnyDimension(
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

    //
    // sources
    //

    /**
     * Source class to the list of its properties that influence this summary.
     *
     * @return list<string>
     */
    public function getInvolvedSourceProperties(): array
    {
        return $this->involvedSourceProperties;
    }
}
