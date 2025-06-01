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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Analytics\Attribute\Dimension;
use Rekalogika\Analytics\Attribute\Groupings;
use Rekalogika\Analytics\Attribute\Hierarchy;
use Rekalogika\Analytics\Attribute\LevelProperty;
use Rekalogika\Analytics\Attribute\Measure;
use Rekalogika\Analytics\Attribute\Partition;
use Rekalogika\Analytics\Attribute\PartitionKey;
use Rekalogika\Analytics\Attribute\PartitionLevel;
use Rekalogika\Analytics\Attribute\Summary;
use Rekalogika\Analytics\Contracts\Model\Partition as DoctrineSummaryPartition;
use Rekalogika\Analytics\Contracts\Summary\PartitionValueResolver;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Exception\MetadataException;
use Rekalogika\Analytics\Exception\SummaryNotFound;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionLevelMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionPathMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionPropertyMetadata;
use Rekalogika\Analytics\Metadata\SourceMetadata;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;
use Rekalogika\Analytics\Util\AttributeUtil;
use Rekalogika\Analytics\Util\LiteralString;
use Rekalogika\Analytics\Util\TranslatableMessage;
use Rekalogika\Analytics\Util\TranslatableUtil;
use Rekalogika\Analytics\ValueResolver\EntityValueResolver;
use Rekalogika\Analytics\ValueResolver\PropertyValueResolver;

final readonly class DefaultSummaryMetadataFactory implements SummaryMetadataFactory
{
    /**
     * @var array<class-string,array<string,list<class-string>>>
     */
    private array $involvedProperties;

    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
        $this->involvedProperties = $this->createInvolvedProperties();
    }


    /**
     * Source class to the mapping of its properties to summary classes that
     * are affected by the change of the source property.
     *
     * @return array<class-string,array<string,list<class-string>>>
     */
    private function createInvolvedProperties(): array
    {
        $involvedProperties = [];

        foreach ($this->getAllSummaryMetadata() as $summaryMetadata) {
            $summaryClass = $summaryMetadata->getSummaryClass();
            $summaryInvolvedProperties = $summaryMetadata->getInvolvedProperties();

            foreach ($summaryInvolvedProperties as $sourceClass => $sourceProperties) {
                foreach ($sourceProperties as $sourceProperty) {
                    $involvedProperties[$sourceClass][$sourceProperty][] = $summaryClass;
                }
            }
        }

        $uniqueInvolvedProperties = [];

        foreach ($involvedProperties as $sourceClass => $sourceProperties) {
            $uniqueInvolvedProperties[$sourceClass] = [];

            foreach ($sourceProperties as $sourceProperty => $summaryClasses) {
                $uniqueInvolvedProperties[$sourceClass][$sourceProperty] = array_values(array_unique($summaryClasses));
            }
        }

        return $uniqueInvolvedProperties;
    }

    #[\Override]
    public function getSourceMetadata(string $sourceClassName): SourceMetadata
    {
        $allPropertiesToSummaryClasses = [];

        $parents = class_parents($sourceClassName);

        if ($parents === false) {
            $parents = [];
        }

        $classes = [$sourceClassName, ...$parents];

        foreach ($classes as $class) {
            foreach ($this->involvedProperties[$class] ?? [] as $property => $summaryClasses) {
                foreach ($summaryClasses as $summaryClass) {
                    $allPropertiesToSummaryClasses[$property][] = $summaryClass;
                }
            }
        }

        return new SourceMetadata(
            class: $sourceClassName,
            propertyToSummaryClasses: $allPropertiesToSummaryClasses,
        );
    }

    /**
     * @param class-string $className
     */
    #[\Override]
    public function isSummary(string $className): bool
    {
        return AttributeUtil::classHasAttribute($className, Summary::class);
    }

    /**
     * @return iterable<string,SummaryMetadata>
     */
    private function getAllSummaryMetadata(): iterable
    {
        foreach ($this->getSummaryClasses() as $summaryClass) {
            yield $summaryClass => $this->getSummaryMetadata($summaryClass);
        }
    }

    #[\Override]
    public function getSummaryClasses(): iterable
    {
        $classes = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            $metadata = $manager->getMetadataFactory()->getAllMetadata();

            foreach ($metadata as $classMetadata) {
                $class = $classMetadata->getName();

                if (!$this->isSummary($class)) {
                    continue;
                }

                $classes[$class] = true;
            }
        }

        yield from array_keys($classes);
    }

    /**
     * @param class-string $summaryClassName
     */
    #[\Override]
    public function getSummaryMetadata(
        string $summaryClassName,
    ): SummaryMetadata {
        $reflectionClass = new \ReflectionClass($summaryClassName);

        // get summary attribute
        $summaryAttribute = AttributeUtil::getClassAttribute(
            class: $summaryClassName,
            attributeClass: Summary::class,
        ) ?? throw new SummaryNotFound($summaryClassName);

        $sourceClasses = $summaryAttribute->getSourceClasses();
        $sourceClassMetadata = [];

        foreach ($sourceClasses as $sourceClass) {
            $sourceClassMetadata[$sourceClass] = $this->getDoctrineClassMetadata($sourceClass);
        }

        $summaryClassMetadata = $this->getDoctrineClassMetadata($summaryClassName);

        $properties = AttributeUtil::getPropertiesOfClass($summaryClassName);

        $dimensionMetadatas = [];
        $measureMetadatas = [];
        $partitionMetadata = null;
        $groupingsProperty = null;

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $dimensionAttribute = AttributeUtil::getPropertyAttribute(
                class: $summaryClassName,
                property: $property,
                attributeClass: Dimension::class,
            );

            $measureAttribute = AttributeUtil::getPropertyAttribute(
                class: $summaryClassName,
                property: $property,
                attributeClass: Measure::class,
            );

            $partitionAttribute = AttributeUtil::getPropertyAttribute(
                class: $summaryClassName,
                property: $property,
                attributeClass: Partition::class,
            );

            $groupingsAttribute = AttributeUtil::getPropertyAttribute(
                class: $summaryClassName,
                property: $property,
                attributeClass: Groupings::class,
            );

            $typeClass = AttributeUtil::getTypeClass($reflectionProperty);

            if ($dimensionAttribute !== null && $measureAttribute !== null) {
                throw new MetadataException('Property cannot have both Dimension and Measure attributes');
            }

            if ($dimensionAttribute !== null) {
                $dimensionMetadata = $this->createDimensionMetadata(
                    summaryProperty: $property,
                    dimensionAttribute: $dimensionAttribute,
                    sourceClasses: $sourceClasses,
                    sourceClassesMetadata: $sourceClassMetadata,
                    summaryClassMetadata: $summaryClassMetadata,
                    typeClass: $typeClass,
                );

                $dimensionMetadatas[$property] = $dimensionMetadata;
            } elseif ($measureAttribute !== null) {
                $measureMetadatas[$property] =
                    $this->createMeasureMetadata(
                        sourceClasses: $sourceClasses,
                        property: $property,
                        measureAttribute: $measureAttribute,
                    );
            } elseif ($partitionAttribute !== null) {
                $partitionMetadata = $this->createPartitionMetadata(
                    summaryProperty: $property,
                    sourceClasses: $sourceClasses,
                    sourceClassesMetadata: $sourceClassMetadata,
                    partitionAttribute: $partitionAttribute,
                    summaryClassMetadata: $summaryClassMetadata,
                );
            } elseif ($groupingsAttribute !== null) {
                $groupingsProperty = $property;
            }
        }

        if ($dimensionMetadatas === []) {
            throw new MetadataException('At least one Dimension attribute is required');
        }

        if ($measureMetadatas === []) {
            throw new MetadataException('At least one Measure attribute is required');
        }

        if ($partitionMetadata === null) {
            throw new MetadataException('Partition attribute is required');
        }

        if ($groupingsProperty === null) {
            throw new MetadataException('Groupings attribute is required');
        }

        $label = $summaryAttribute->getLabel() ?? $reflectionClass->getShortName();
        $label = TranslatableUtil::normalize($label);

        return new SummaryMetadata(
            sourceClasses: $sourceClasses,
            summaryClass: $summaryClassName,
            partition: $partitionMetadata,
            dimensions: $dimensionMetadatas,
            measures: $measureMetadatas,
            groupingsProperty: $groupingsProperty,
            label: $label,
        );
    }

    /**
     * @param non-empty-list<class-string> $sourceClasses
     * @param array<class-string,ClassMetadataWrapper> $sourceClassesMetadata
     * @param class-string|null $typeClass
     */
    private function createDimensionMetadata(
        string $summaryProperty,
        array $sourceClasses,
        Dimension $dimensionAttribute,
        array $sourceClassesMetadata,
        ?string $typeClass,
        ClassMetadataWrapper $summaryClassMetadata,
    ): DimensionMetadata {
        $sourceProperty = $dimensionAttribute->getSource();

        // if source property is not provided, use summary property name as
        // source property name

        if ($sourceProperty === null) {
            $sourceProperty = $summaryProperty;
        }

        // handle cases if source property is scalar

        if (!\is_array($sourceProperty)) {
            $newSourceProperty = [];

            foreach ($sourceClasses as $sourceClass) {
                $newSourceProperty[$sourceClass] = $sourceProperty;
            }

            $sourceProperty = $newSourceProperty;
        }

        // normalize source property

        $newSourceProperty = [];

        foreach ($sourceProperty as $sourceClass => $curProperty) {
            if ($curProperty instanceof ValueResolver) {
                $newSourceProperty[$sourceClass] = $curProperty;

                continue;
            }

            $sourceClassMetadata = $sourceClassesMetadata[$sourceClass]
                ?? throw new MetadataException(\sprintf('Source class not found: %s', $sourceClass));

            $isEntity = $sourceClassMetadata->isPropertyEntity($curProperty);
            $isField = $sourceClassMetadata->isPropertyField($curProperty);

            if ($isEntity) {
                $newSourceProperty[$sourceClass] = new EntityValueResolver($curProperty);
            } elseif ($isField) {
                $newSourceProperty[$sourceClass] = new PropertyValueResolver($curProperty);
            } else {
                // @todo ensure validity
                $newSourceProperty[$sourceClass] = new PropertyValueResolver($curProperty);
            }
        }

        $sourceProperty = $newSourceProperty;

        if ($summaryClassMetadata->isPropertyEmbedded($summaryProperty)) {
            $embeddedClass = $summaryClassMetadata
                ->getEmbeddedClassOfProperty($summaryProperty);

            $dimensionHierarchy = $this->createDimensionHierarchyMetadata(
                hierarchyPropertyName: $summaryProperty,
                hierarchyClass: $embeddedClass,
            );
        } else {
            $dimensionHierarchy = null;
        }

        $label = $dimensionAttribute->getLabel() ?? $summaryProperty;
        $label = TranslatableUtil::normalize($label);

        $nullLabel = TranslatableUtil::normalize($dimensionAttribute->getNullLabel())
            ?? new TranslatableMessage('(None)');

        return new DimensionMetadata(
            source: $sourceProperty,
            summaryProperty: $summaryProperty,
            label: $label,
            sourceTimeZone: $dimensionAttribute->getSourceTimeZone(),
            summaryTimeZone: $dimensionAttribute->getSummaryTimeZone(),
            hierarchy: $dimensionHierarchy,
            orderBy: $dimensionAttribute->getOrderBy(),
            typeClass: $typeClass,
            nullLabel: $nullLabel,
            mandatory: $dimensionAttribute->isMandatory(),
        );
    }

    /**
     * @param non-empty-list<class-string> $sourceClasses
     * @param array<class-string,ClassMetadataWrapper> $sourceClassesMetadata
     */
    private function createPartitionMetadata(
        string $summaryProperty,
        array $sourceClasses,
        array $sourceClassesMetadata,
        Partition $partitionAttribute,
        ClassMetadataWrapper $summaryClassMetadata,
    ): PartitionMetadata {
        $sourceProperty = $partitionAttribute->getSource();

        // if source property is not provided, use summary property name as
        // source property name

        if ($sourceProperty === null) {
            $sourceProperty = $summaryProperty;
        }

        // handle cases if source property is scalar

        if (!\is_array($sourceProperty)) {
            $newSourceProperty = [];

            foreach ($sourceClasses as $sourceClass) {
                $newSourceProperty[$sourceClass] = $sourceProperty;
            }

            $sourceProperty = $newSourceProperty;
        }

        // normalize source property

        $newSourceProperty = [];

        foreach ($sourceProperty as $sourceClass => $curProperty) {
            if ($curProperty instanceof PartitionValueResolver) {
                $newSourceProperty[$sourceClass] = $curProperty;

                continue;
            }

            $sourceClassMetadata = $sourceClassesMetadata[$sourceClass]
                ?? throw new MetadataException(\sprintf('Source class not found: %s', $sourceClass));

            $isField = $sourceClassMetadata->isPropertyField($curProperty);

            if (!$isField) {
                throw new MetadataException('Partition property must be field');
            }

            $newSourceProperty[$sourceClass] = new PropertyValueResolver($curProperty);
        }

        $sourceProperty = $newSourceProperty;

        if (!$summaryClassMetadata->isPropertyEmbedded($summaryProperty)) {
            throw new MetadataException('Partition property must be embedded');
        }

        $partitionClass = $summaryClassMetadata
            ->getEmbeddedClassOfProperty($summaryProperty);

        if (!is_a($partitionClass, DoctrineSummaryPartition::class, true)) {
            throw new MetadataException('Partition class must implement Partition interface');
        }

        // get partition level and partition id property names

        $partitionLevelPropertyName = null;
        $partitionKeyPropertyName = null;
        $partitionKeyClassifier = null;

        $properties = AttributeUtil::getPropertiesOfClass($partitionClass);

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $partitionLevelAttribute = AttributeUtil::getPropertyAttribute(
                class: $partitionClass,
                property: $property,
                attributeClass: PartitionLevel::class,
            );

            $partitionKeyAttribute = AttributeUtil::getPropertyAttribute(
                class: $partitionClass,
                property: $property,
                attributeClass: PartitionKey::class,
            );

            if ($partitionLevelAttribute !== null) {
                if ($partitionLevelPropertyName !== null) {
                    throw new MetadataException('Multiple partition level properties found');
                }

                $partitionLevelPropertyName = $property;
            }

            if ($partitionKeyAttribute !== null) {
                if ($partitionKeyPropertyName !== null) {
                    throw new MetadataException('Multiple partition id properties found');
                }

                $partitionKeyPropertyName = $property;
                $partitionKeyClassifier = $partitionKeyAttribute->getClassifier();
            }
        }

        if ($partitionLevelPropertyName === null) {
            throw new MetadataException('Partition level property not found');
        }

        if ($partitionKeyPropertyName === null) {
            throw new MetadataException('Partition id property not found');
        }

        if ($partitionKeyClassifier === null) {
            throw new MetadataException('Partition id classifier not found');
        }

        return new PartitionMetadata(
            source: $sourceProperty,
            summaryProperty: $summaryProperty,
            partitionClass: $partitionClass,
            partitionLevelProperty: $partitionLevelPropertyName,
            partitionKeyProperty: $partitionKeyPropertyName,
            partitionKeyClassifier: $partitionKeyClassifier,
        );
    }

    /**
     * @param non-empty-list<class-string> $sourceClasses
     */
    private function createMeasureMetadata(
        array $sourceClasses,
        string $property,
        Measure $measureAttribute,
    ): MeasureMetadata {
        $function = $measureAttribute->getFunction();

        $unit = $measureAttribute->getUnit();

        if ($unit !== null) {
            $unitSignature = sha1(serialize($unit));
        } else {
            $unitSignature = null;
        }

        $unit = TranslatableUtil::normalize($unit);

        if (!\is_array($function)) {
            $newFunction = [];

            foreach ($sourceClasses as $sourceClass) {
                $newFunction[$sourceClass] = $function;
            }

            $function = $newFunction;
        }

        // make sure all functions are of the same class

        $class = null;

        foreach ($function as $curFunction) {
            if ($class === null) {
                $class = $curFunction::class;
            } elseif ($class !== $curFunction::class) {
                throw new MetadataException('All functions must be of the same class');
            }
        }

        $label = TranslatableUtil::normalize($measureAttribute->getLabel())
            ?? new LiteralString($property);

        return new MeasureMetadata(
            function: $function,
            summaryProperty: $property,
            label: $label,
            unit: $unit,
            unitSignature: $unitSignature,
        );
    }

    /**
     * @param class-string $class
     */
    private function getDoctrineClassMetadata(string $class): ClassMetadataWrapper
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager === null) {
            throw new MetadataException(\sprintf('No manager found for class %s', $class));
        }

        $classMetadata = $manager->getClassMetadata($class);

        if (!$classMetadata instanceof ClassMetadata) {
            throw new MetadataException('Unsupported class metadata object returned');
        }

        return new ClassMetadataWrapper($classMetadata);
    }

    /**
     * @param class-string $hierarchyClass
     */
    private function createDimensionHierarchyMetadata(
        string $hierarchyClass,
        string $hierarchyPropertyName,
    ): DimensionHierarchyMetadata {
        $hierarchyAttribute = AttributeUtil::getClassAttribute(
            class: $hierarchyClass,
            attributeClass: Hierarchy::class,
        ) ?? throw new MetadataException('DimensionHierarchy attribute is required, but not found');

        // collect properties & levels

        $properties = AttributeUtil::getPropertiesOfClass($hierarchyClass);

        $levels = [];

        foreach ($properties as $reflectionProperty) {
            $property = $reflectionProperty->getName();

            $dimensionLevelAttribute = AttributeUtil::getPropertyAttribute(
                class: $hierarchyClass,
                property: $property,
                attributeClass: LevelProperty::class,
            );

            if ($dimensionLevelAttribute === null) {
                continue;
            }

            $level = $dimensionLevelAttribute->getLevel();
            $name = $property;
            $label = $dimensionLevelAttribute->getLabel() ?? $name;

            if (\is_string($label)) {
                $label = new LiteralString($label);
            }

            $valueResolver = $dimensionLevelAttribute->getValueResolver();
            $typeClass = AttributeUtil::getTypeClass($reflectionProperty);

            $nullLabel = TranslatableUtil::normalize($dimensionLevelAttribute->getNullLabel())
                ?? new TranslatableMessage('(None)');

            $dimensionPropertyMetadata = new DimensionPropertyMetadata(
                name: $name,
                hierarchyName: $hierarchyPropertyName,
                label: $label,
                valueResolver: $valueResolver,
                typeClass: $typeClass,
                nullLabel: $nullLabel,
            );

            $levels[$level][] = $dimensionPropertyMetadata;
        }

        // create levels

        $dimensionLevelsMetadata = [];

        foreach ($levels as $level => $dimensionPropertyMetadatas) {
            $dimensionLevelsMetadata[$level] = new DimensionLevelMetadata(
                levelId: $level,
                properties: $dimensionPropertyMetadatas,
            );
        }

        // create paths

        $dimensionPathsMetadata = [];

        foreach ($hierarchyAttribute->getPaths() as $path) {
            $levels = [];

            foreach ($path as $level) {
                $levels[] = $dimensionLevelsMetadata[$level]
                    ?? throw new MetadataException(\sprintf('Level not found: %d', $level));
            }

            if ($levels === []) {
                throw new MetadataException('At least one level is required');
            }

            $dimensionPathsMetadata[] = new DimensionPathMetadata(
                levels: $levels,
            );
        }

        // make sure at least one path is defined

        if ($dimensionPathsMetadata === []) {
            throw new MetadataException('At least one path is required');
        }

        // ensure the lowest level of each path is the same

        $lowest = null;

        foreach ($dimensionPathsMetadata as $dimensionPathMetadata) {
            $lowestLevel = $dimensionPathMetadata->getLowestLevel()->getLevelId();

            if ($lowest === null) {
                $lowest = $lowestLevel;
            } elseif ($lowest !== $lowestLevel) {
                throw new MetadataException('All paths must have the same lowest level');
            }
        }

        // return the hierarchy metadata

        return new DimensionHierarchyMetadata(
            hierarchyClass: $hierarchyClass,
            paths: $dimensionPathsMetadata,
        );
    }
}
