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
use Rekalogika\Analytics\Contracts\Summary\GroupingStrategy;
use Rekalogika\Analytics\Metadata\AttributeCollection\AttributeCollection;
use Rekalogika\Analytics\Metadata\Groupings\DefaultGroupByExpressions;
use Rekalogika\Analytics\Metadata\Implementation\RootGroupingStrategy;
use Rekalogika\DoctrineAdvancedGroupBy\GroupBy;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Metadata of a summary class: everything the framework derives from the
 * class's attributes and Doctrine mapping. Obtain it from
 * `SummaryMetadataFactory`.
 *
 * Dimensions form a tree. The root dimensions are the properties of the
 * summary class with the `Dimension` attribute. A root dimension whose type
 * is a dimension group has the group's dimensions as children, and so on. The
 * leaf dimensions are the ones without children; they are the only ones that
 * can be used in queries, and are referred to by their fully qualified name,
 * e.g. `time.civil.year`.
 */
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
     * @param class-string $sourceClass The entity class being summarized.
     * @param class-string $summaryClass The summary entity class.
     * @param PartitionMetadata $partition The partition of the summary.
     * @param non-empty-array<string,DimensionMetadata> $dimensions The root
     * dimensions, keyed by property name.
     * @param non-empty-array<string,MeasureMetadata> $measures The measures,
     * keyed by property name.
     * @param string $groupingsProperty The property with the `Groupings`
     * attribute.
     * @param AttributeCollection $attributes The attributes of the summary
     * class.
     * @param TranslatableInterface $label The label of the summary.
     * @param GroupingStrategy|null $groupingStrategy The grouping strategy of
     * the root dimensions. If null, the summary stores the cube of all the
     * root dimensions.
     * @throws MetadataException If the grouping strategy is invalid or
     * produces no grouping set.
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
        ?GroupingStrategy $groupingStrategy = null,
    ) {
        $allProperties = [];

        //
        // partition
        //

        $this->partition = $partition->withSummaryMetadata($this);
        $allProperties[$partition->getName()] = $this->partition;

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

        foreach ($dimensions as $dimension) {
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

            foreach ($dimension->getDescendants() as $descendant) {
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

        $childrenExpressions = [];

        foreach ($this->rootDimensions as $key => $dimension) {
            $childrenExpressions[$key] = $dimension->getGroupByExpression();
        }

        $childrenExpressions = new DefaultGroupByExpressions($childrenExpressions);

        if ($groupingStrategy === null) {
            // default: the cube of every root dimension. Each item returned
            // by the root strategy becomes a separate item in the GROUP BY
            // clause, so the result is the cross product of all of them.

            $groupingSets = (new RootGroupingStrategy())
                ->getGroupByExpression($childrenExpressions);

            $groupBy = new GroupBy();

            foreach ($groupingSets as $groupingSet) {
                $groupBy->add($groupingSet);
            }
        } else {
            // user-supplied strategy: the result is the complete group by
            // expression of the summary, the same way as the result of a
            // dimension group's strategy is the complete expression of that
            // dimension group.

            try {
                $expression = $groupingStrategy
                    ->getGroupByExpression($childrenExpressions);
            } catch (InvalidArgumentException $e) {
                throw new MetadataException(\sprintf(
                    'Invalid grouping strategy in summary class "%s": %s',
                    $summaryClass,
                    $e->getMessage(),
                ), previous: $e);
            }

            if (\count($expression) === 0) {
                throw new MetadataException(\sprintf(
                    'The grouping strategy of summary class "%s" does not produce any grouping set.',
                    $summaryClass,
                ));
            }

            $groupBy = new GroupBy($expression);
        }

        $this->groupByExpression = $groupBy;
    }

    /**
     * The summary entity class.
     *
     * @return class-string
     */
    public function getSummaryClass(): string
    {
        return $this->summaryClass;
    }

    /**
     * The entity class being summarized.
     *
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    /**
     * The label of the summary, as given in the `Summary` attribute, or the
     * short class name if none was given.
     */
    public function getLabel(): TranslatableInterface
    {
        return $this->label;
    }

    /**
     * The attributes of the summary class, including custom ones.
     */
    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
    }

    //
    // all properties
    //

    /**
     * Returns every property having analytics metadata: all the dimensions at
     * every level of the tree, the measures, and the partition, keyed by
     * fully qualified name.
     *
     * @return array<string,PropertyMetadata>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Returns a property by its fully qualified name, e.g. `time.civil.year`
     * or `count`.
     *
     * @throws MetadataException If there is no such property.
     */
    public function getProperty(string $propertyName): PropertyMetadata
    {
        return $this->properties[$propertyName]
            ?? throw new MetadataException(\sprintf(
                'Property not found: %s',
                $propertyName,
            ));
    }

    /**
     * Alias of `getProperties()`, for treating the summary as the root of the
     * property tree.
     *
     * @return array<string,PropertyMetadata>
     */
    public function getChildren(): array
    {
        return $this->getProperties();
    }

    /**
     * Alias of `getProperty()`, for treating the summary as the root of the
     * property tree.
     */
    public function getChild(string $propertyName): PropertyMetadata
    {
        return $this->getProperty($propertyName);
    }

    //
    // partition
    //

    /**
     * The partition of the summary.
     */
    public function getPartition(): PartitionMetadata
    {
        return $this->partition;
    }

    /**
     * The name of the property with the `Groupings` attribute, which stores
     * the grouping set of each row.
     */
    public function getGroupingsProperty(): string
    {
        return $this->groupingsProperty;
    }

    //
    // dimensions
    //

    /**
     * Returns all the root dimensions, i.e. the properties of the summary
     * class having the `Dimension` attribute. The children of a dimension
     * group are not included in this list.
     *
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getRootDimensions(): array
    {
        return $this->rootDimensions;
    }

    /**
     * Returns a root dimension by its name.
     *
     * @throws MetadataException If there is no such root dimension.
     */
    public function getRootDimension(string $dimensionName): DimensionMetadata
    {
        return $this->rootDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }


    /**
     * Returns all the leaf dimensions, i.e. the dimensions without children,
     * keyed by fully qualified name. These are the dimensions that can be
     * used in queries and that have a column in the summary table. A root
     * dimension that is not a dimension group is also a leaf.
     *
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getLeafDimensions(): array
    {
        return $this->leafDimensions;
    }

    /**
     * Returns a leaf dimension by its fully qualified name.
     *
     * @throws MetadataException If there is no such leaf dimension.
     */
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
     * dimensions, keyed by fully qualified name.
     *
     * @return non-empty-array<string,DimensionMetadata>
     */
    public function getAllDimensions(): array
    {
        return $this->allDimensions;
    }

    /**
     * Returns a dimension at any level of the tree by its fully qualified
     * name.
     *
     * @throws MetadataException If there is no such dimension.
     */
    public function getDimension(string $dimensionName): DimensionMetadata
    {
        return $this->allDimensions[$dimensionName]
            ?? throw new MetadataException(\sprintf(
                'Dimension not found: %s',
                $dimensionName,
            ));
    }

    /**
     * Returns the leaf dimension by its DQL alias, the identifier the
     * framework uses for the dimension in the queries it generates.
     *
     * @see DimensionMetadata::getDqlAlias()
     * @throws MetadataException If no leaf dimension has the alias.
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
     * Returns all the measures, keyed by property name.
     *
     * @return non-empty-array<string,MeasureMetadata>
     */
    public function getMeasures(): array
    {
        return $this->measures;
    }

    /**
     * Returns a measure by its property name.
     *
     * @throws MetadataException If there is no such measure.
     */
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
     * The properties of the source class that influence this summary, as
     * declared by the value resolvers and aggregate functions of the
     * dimensions and measures. Only the first segment of each property path
     * is kept, e.g. `customer` for `customer.country`, because a change to
     * the `customer` association is what Doctrine reports. The refresh engine
     * uses this to decide whether a change to a source entity affects the
     * summary.
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

    /**
     * The complete GROUP BY clause used when rolling up the source entities
     * into the summary table. It determines which grouping sets are stored.
     * A copy is returned, so the caller may modify it.
     */
    public function getGroupByExpression(): GroupBy
    {
        return clone $this->groupByExpression;
    }

    //
    // validity checkers
    //

    /**
     * Checks that every name is a leaf dimension or the special `@values`
     * dimension.
     *
     * @param list<string> $dimensions
     * @throws InvalidArgumentException If one of the names is not valid.
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
     * Checks that every name is a measure.
     *
     * @param list<string> $measures
     * @throws InvalidArgumentException If one of the names is not valid.
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
     * Checks that every name is a leaf dimension, a measure, or the special
     * `@values` dimension.
     *
     * @param list<string> $properties
     * @throws InvalidArgumentException If one of the names is not valid.
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
