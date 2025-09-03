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

use Rekalogika\Analytics\Contracts\Exception\InvalidArgumentException;
use Rekalogika\Analytics\Contracts\Exception\MetadataException;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\Analytics\Metadata\Implementation\RootGroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
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
    private array $rootDimensions;

    /**
     * @var non-empty-array<string,DimensionMetadata>
     */
    private array $leafDimensions;

    /**
     * @var non-empty-array<string,DimensionMetadata>
     */
    private array $allDimensions;

    /**
     * @var array<string,DimensionMetadata>
     */
    private array $aliasToDimension;

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

    private GroupBy $groupByExpression;

    /**
     *
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
        $allProperties[$partition->getName()] = $partition;

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

        $allDimensions = [];
        $rootDimensions = [];
        $leafDimensions = [];
        $aliasToDimension = [];

        foreach ($dimensions as $dimensionKey => $dimension) {
            $dimension = $dimension->withSummaryMetadata(
                summaryMetadata: $this,
            );

            $allDimensions[$dimension->getName()] = $dimension;
            $rootDimensions[$dimension->getName()] = $dimension;
            $allProperties[$dimension->getName()] = $dimension;

            if (!$dimension->hasChildren()) {
                $leafDimensions[$dimension->getName()] = $dimension;
                $aliasToDimension[$dimension->getDqlAlias()] = $dimension;
            }

            foreach ($dimension->getDescendants() as $dimensionKey => $descendant) {
                if (!$descendant->hasChildren()) {
                    // this is a leaf dimension
                    $leafDimensions[$descendant->getName()] = $descendant;
                    $aliasToDimension[$descendant->getDqlAlias()] = $descendant;
                }

                $allDimensions[$descendant->getName()] = $descendant;
                $allProperties[$descendant->getName()] = $descendant;
            }
        }

        /** @var non-empty-array<string,DimensionMetadata> $rootDimensions */
        $this->allDimensions = $allDimensions;

        /** @var non-empty-array<string,DimensionMetadata> $rootDimensions */
        $this->rootDimensions = $rootDimensions;

        /** @var non-empty-array<string,DimensionMetadata> $leafDimensions */
        $this->leafDimensions = $leafDimensions;

        /** @var non-empty-array<string,DimensionMetadata> $leafDimensions */
        $this->aliasToDimension = $aliasToDimension;

        //
        // all properties
        //

        $this->properties = $allProperties;

        //
        // involved properties
        //

        $properties = [];
        $dimensionsAndMeasures = [
            ...$this->rootDimensions,
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

        //
        // group by expression
        //

        $strategy = new RootGroupingStrategy();
        $childrenExpressions = [];

        foreach ($this->rootDimensions as $key => $dimension) {
            $childrenExpressions[$key] = $dimension->getGroupByExpression();
        }

        $childrenExpressions = new DefaultGroupByExpressions($childrenExpressions);
        $groupingSets = $strategy->getGroupByExpression($childrenExpressions);
        $groupBy = new GroupBy();

        foreach ($groupingSets as $groupingSet) {
            $groupBy->add($groupingSet);
        }

        $this->groupByExpression = $groupBy;
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

    /**
     * @return array<string,PropertyMetadata>
     */
    public function getChildren(): array
    {
        return $this->getProperties();
    }

    public function getChild(string $propertyName): PropertyMetadata
    {
        return $this->getProperty($propertyName);
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
        return $this->rootDimensions;
    }

    public function getRootDimension(string $dimensionName): DimensionMetadata
    {
        return $this->rootDimensions[$dimensionName]
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
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getLeafDimensions(): array
    {
        return $this->leafDimensions;
    }

    public function getLeafDimension(string $dimensionName): DimensionMetadata
    {
        return $this->leafDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Leaf dimension not found: %s',
                $dimensionName,
            ));
    }

    /**
     * Returns all the dimensions, including root, leaf, and intermediate
     * dimensions.
     *
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getAllDimensions(): array
    {
        return $this->allDimensions;
    }

    public function getDimension(string $dimensionName): DimensionMetadata
    {
        return $this->allDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }

    /**
     * Returns the dimension by its DQL alias.
     *
     * @return DimensionMetadata
     */
    public function getDimensionByAlias(string $alias): DimensionMetadata
    {
        return $this->aliasToDimension[$alias]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found by alias: %s',
                $alias,
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

    //
    // group by expression
    //

    public function getGroupByExpression(): GroupBy
    {
        return clone $this->groupByExpression;
    }

    //
    // validity checkers
    //

    /**
     * @param list<string> $dimensions
     */
    public function ensureDimensionsValid(array $dimensions): void
    {
        foreach ($dimensions as $dimension) {
            if ($dimension === '@values') {
                // special dimension to get all leaf dimensions
                continue;
            }

            if (!\array_key_exists($dimension, $this->leafDimensions)) {
                throw new InvalidArgumentException(\sprintf(
                    'Dimension not found: %s',
                    $dimension,
                ));
            }
        }
    }

    /**
     * @param list<string> $measures
     */
    public function ensureMeasuresValid(array $measures): void
    {
        foreach ($measures as $measure) {
            if (!\array_key_exists($measure, $this->measures)) {
                throw new InvalidArgumentException(\sprintf(
                    'Measure not found: %s',
                    $measure,
                ));
            }
        }
    }

    /**
     * @param list<string> $properties
     */
    public function ensurePropertiesValid(array $properties): void
    {
        foreach ($properties as $property) {
            if ($property === '@values') {
                continue;
            }

            if (
                !\array_key_exists($property, $this->leafDimensions)
                && !\array_key_exists($property, $this->measures)
            ) {
                throw new InvalidArgumentException(\sprintf(
                    'Property not found: %s',
                    $property,
                ));
            }
        }
    }
}
