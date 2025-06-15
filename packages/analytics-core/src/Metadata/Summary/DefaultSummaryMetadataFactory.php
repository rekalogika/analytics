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
use Rekalogika\Analytics\Contracts\Metadata\Dimension;
use Rekalogika\Analytics\Contracts\Metadata\Groupings;
use Rekalogika\Analytics\Contracts\Metadata\Hierarchy;
use Rekalogika\Analytics\Contracts\Metadata\Measure;
use Rekalogika\Analytics\Contracts\Metadata\Partition;
use Rekalogika\Analytics\Contracts\Metadata\PartitionKey;
use Rekalogika\Analytics\Contracts\Metadata\PartitionLevel;
use Rekalogika\Analytics\Contracts\Metadata\Summary;
use Rekalogika\Analytics\Contracts\Model\Partition as DoctrineSummaryPartition;
use Rekalogika\Analytics\Contracts\Summary\ValueResolver;
use Rekalogika\Analytics\Core\Exception\MetadataException;
use Rekalogika\Analytics\Core\Exception\SummaryNotFound;
use Rekalogika\Analytics\Core\Util\AttributeUtil;
use Rekalogika\Analytics\Core\Util\LiteralString;
use Rekalogika\Analytics\Core\Util\TranslatableMessage;
use Rekalogika\Analytics\Core\Util\TranslatableUtil;
use Rekalogika\Analytics\Core\ValueResolver\IdentifierValue;
use Rekalogika\Analytics\Core\ValueResolver\PropertyValue;
use Rekalogika\Analytics\Engine\Doctrine\ClassMetadataWrapper;
use Rekalogika\Analytics\Metadata\DimensionHierarchy\DimensionHierarchyMetadata;
use Rekalogika\Analytics\Metadata\DimensionHierarchyMetadataFactory;
use Rekalogika\Analytics\Metadata\SummaryMetadataFactory;

final readonly class DefaultSummaryMetadataFactory implements SummaryMetadataFactory
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private DimensionHierarchyMetadataFactory $dimensionHierarchyMetadataFactory,
    ) {}

    /**
     * @param class-string $className
     */
    #[\Override]
    public function isSummary(string $className): bool
    {
        return \in_array($className, $this->getSummaryClasses(), true);
    }

    #[\Override]
    public function getSummaryClasses(): array
    {
        $classes = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            $metadata = $manager->getMetadataFactory()->getAllMetadata();

            foreach ($metadata as $classMetadata) {
                $class = $classMetadata->getName();

                if (!AttributeUtil::classHasAttribute($class, Summary::class)) {
                    continue;
                }

                $classes[$class] = true;
            }
        }

        return array_keys($classes);
    }

    /**
     * @todo remove $sourceClasses remnant, change to single $sourceClass
     * instead
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

        $sourceClasses = [$summaryAttribute->getSourceClass()];
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
                        summaryClassName: $summaryClassName,
                        sourceClasses: $sourceClasses,
                        property: $property,
                        measureAttribute: $measureAttribute,
                        typeClass: $typeClass,
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
     * @todo change $sourceProperty to be a single ValueResolver instead of an
     * array
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

        // change scalar source to array

        $newSourceProperty = [];

        foreach ($sourceClasses as $sourceClass) {
            $newSourceProperty[$sourceClass] = $sourceProperty;
        }

        $sourceProperty = $newSourceProperty;

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
                $newSourceProperty[$sourceClass] = new IdentifierValue($curProperty);
            } elseif ($isField) {
                $newSourceProperty[$sourceClass] = new PropertyValue($curProperty);
            } else {
                // @todo ensure validity
                $newSourceProperty[$sourceClass] = new PropertyValue($curProperty);
            }
        }

        $sourceProperty = $newSourceProperty;

        // label

        $label = $dimensionAttribute->getLabel() ?? $summaryProperty;
        $label = TranslatableUtil::normalize($label);

        $nullLabel = TranslatableUtil::normalize($dimensionAttribute->getNullLabel())
            ?? new TranslatableMessage('(None)');

        // hierarchy

        if ($summaryClassMetadata->isPropertyEmbedded($summaryProperty)) {
            $embeddedClass = $summaryClassMetadata
                ->getEmbeddedClassOfProperty($summaryProperty);

            $dimensionHierarchy = $this->dimensionHierarchyMetadataFactory
                ->getDimensionHierarchyMetadata($embeddedClass);

            $dimensionProperties = $this->createDimensionProperties(
                summaryProperty: $summaryProperty,
                dimensionHierarchy: $dimensionHierarchy,
            );
        } else {
            $dimensionHierarchy = null;
            $dimensionProperties = [];
        }

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
            hidden: $dimensionAttribute->isHidden(),
            properties: $dimensionProperties,
        );
    }

    /**
     * @return array<string,DimensionPropertyMetadata>
     */
    private function createDimensionProperties(
        string $summaryProperty,
        DimensionHierarchyMetadata $dimensionHierarchy,
    ): array {
        $dimensionProperties = [];

        foreach ($dimensionHierarchy->getProperties() as $dimensionLevelProperty) {
            $dimensionProperty = new DimensionPropertyMetadata(
                summaryProperty: $summaryProperty,
                hierarchyProperty: $dimensionLevelProperty->getName(),
                label: $dimensionLevelProperty->getLabel(),
                nullLabel: $dimensionLevelProperty->getNullLabel(),
                typeClass: $dimensionLevelProperty->getTypeClass(),
                dimensionLevelProperty: $dimensionLevelProperty,
                hidden: $dimensionLevelProperty->isHidden(),
            );

            $dimensionProperties[$dimensionProperty->getSummaryProperty()] = $dimensionProperty;
        }

        return $dimensionProperties;
    }

    /**
     * @todo change $sourceProperty to be a single ValueResolver instead of an
     * array
     * @param non-empty-list<class-string> $sourceClasses
     * @param array<class-string,ClassMetadataWrapper> $sourceClassesMetadata
     * @param Partition<mixed> $partitionAttribute
     */
    private function createPartitionMetadata(
        string $summaryProperty,
        array $sourceClasses,
        array $sourceClassesMetadata,
        Partition $partitionAttribute,
        ClassMetadataWrapper $summaryClassMetadata,
    ): PartitionMetadata {
        $sourceProperty = $partitionAttribute->getSource();

        // change scalar source to array

        $newSourceProperty = [];

        foreach ($sourceClasses as $sourceClass) {
            $newSourceProperty[$sourceClass] = $sourceProperty;
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
     * @param class-string $summaryClassName
     * @param non-empty-list<class-string> $sourceClasses
     * @param class-string|null $typeClass
     */
    private function createMeasureMetadata(
        string $summaryClassName,
        array $sourceClasses,
        string $property,
        Measure $measureAttribute,
        ?string $typeClass,
    ): MeasureMetadata {
        $function = $measureAttribute->getFunction();

        $unit = $measureAttribute->getUnit();

        if ($unit !== null) {
            $unitSignature = sha1(serialize($unit));
        } else {
            $unitSignature = null;
        }

        $unit = TranslatableUtil::normalize($unit);

        // change scalar function to array

        $newFunction = [];

        foreach ($sourceClasses as $sourceClass) {
            $newFunction[$sourceClass] = $function;
        }

        $function = $newFunction;

        // make sure all functions are of the same class

        $class = null;

        foreach ($function as $curFunction) {
            if ($class === null) {
                $class = $curFunction::class;
            } elseif ($class !== $curFunction::class) {
                throw new MetadataException('All functions must be of the same class');
            }
        }

        // determine whether the measure is virtual or not

        $classMetadata = $this->getDoctrineClassMetadata($summaryClassName);
        $virtual = !$classMetadata->hasProperty($property);

        $label = TranslatableUtil::normalize($measureAttribute->getLabel())
            ?? new LiteralString($property);

        return new MeasureMetadata(
            function: $function,
            summaryProperty: $property,
            label: $label,
            typeClass: $typeClass,
            unit: $unit,
            unitSignature: $unitSignature,
            virtual: $virtual,
            hidden: $measureAttribute->isHidden(),
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
}
